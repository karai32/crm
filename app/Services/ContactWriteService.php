<?php

final class ContactWriteService
{
    public const NAME_REQUIRED = 'contacts.name_required';
    public const EMAIL_INVALID = 'contacts.invalid_email';
    public const EMAIL_DUPLICATE = 'contacts.email_already_used';
    public const INTERNAL_EMAIL = 'contacts.internal_email';
    public const NOT_FOUND = 'contacts.not_found';

    private ContactRepository $contacts;
    private EntityTagRepository $entityTags;
    private CustomFieldRepository $customFields;

    public function __construct(
        ?ContactRepository $contacts = null,
        ?EntityTagRepository $entityTags = null,
        ?CustomFieldRepository $customFields = null
    ) {
        $this->contacts = $contacts ?? new ContactRepository();
        $this->entityTags = $entityTags ?? new EntityTagRepository();
        $this->customFields = $customFields ?? new CustomFieldRepository();
    }

    public function create(
        array $data,
        array $tagIds = [],
        array $clientIds = [],
        array $customFields = [],
        array $customValues = [],
        bool $applyCustomFieldDefaults = true,
        bool $checkEmailDns = true
    ): int {
        $data = $this->prepare($data, $checkEmailDns);
        $this->validate($data, null, true);

        return $this->transaction(function () use (
            $data,
            $tagIds,
            $clientIds,
            $customFields,
            $customValues,
            $applyCustomFieldDefaults
        ): int {
            $id = $this->contacts->create($data);
            $this->entityTags->sync('contact', $id, $tagIds);
            $this->contacts->syncClients($id, $clientIds);

            if ($customFields !== []) {
                $this->customFields->saveValues(
                    'contact',
                    $id,
                    $customFields,
                    $customValues,
                    $applyCustomFieldDefaults
                );
            }

            return $id;
        });
    }

    /**
     * Null relation/custom-field arguments preserve the corresponding current
     * values. An explicitly supplied empty array clears a relation.
     */
    public function update(
        int $id,
        array $changes,
        ?array $tagIds = null,
        ?array $clientIds = null,
        ?array $customFields = null,
        array $customValues = [],
        bool $checkEmailDns = true
    ): void {
        $current = $this->contacts->find($id);
        if ($current === null) {
            throw new WriteException(self::NOT_FOUND, 'Contact not found.', 404);
        }

        $emailChanged = array_key_exists('email', $changes)
            && $this->nullableString($changes['email']) !== $this->nullableString($current['email'] ?? null);
        $hasManualEmailState = array_key_exists('is_corporate_email', $changes)
            && array_key_exists('email_status', $changes);

        $data = array_replace($current, $changes);
        if ($emailChanged && !$hasManualEmailState) {
            unset($data['is_corporate_email'], $data['email_status']);
        }
        $data = $this->prepare($data, $checkEmailDns);
        $this->validate($data, $id, false);

        $this->transaction(function () use (
            $id,
            $data,
            $tagIds,
            $clientIds,
            $customFields,
            $customValues
        ): void {
            $this->contacts->update($id, $data);

            if ($tagIds !== null) {
                $this->entityTags->sync('contact', $id, $tagIds);
            }
            if ($clientIds !== null) {
                $this->contacts->syncClients($id, $clientIds);
            }
            if ($customFields !== null && $customFields !== []) {
                $this->customFields->saveValues('contact', $id, $customFields, $customValues);
            }
        });
    }

    private function prepare(array $data, bool $checkEmailDns): array
    {
        $prepared = [
            'full_name' => trim((string) ($data['full_name'] ?? '')),
            'email' => $this->nullableString($data['email'] ?? null),
            'phone' => $this->nullableString($data['phone'] ?? null),
            'company' => trim((string) ($data['company'] ?? '')),
        ];

        $inspection = $prepared['email'] !== null
            && array_key_exists('is_corporate_email', $data)
            && array_key_exists('email_status', $data)
            ? [
                'is_corporate_email' => $data['is_corporate_email'] === null
                    ? null
                    : (int) (bool) $data['is_corporate_email'],
                'email_status' => $this->nullableString($data['email_status']),
            ]
            : EmailInspector::inspect($prepared['email'], $checkEmailDns);

        return array_merge($prepared, $inspection);
    }

    private function validate(array $data, ?int $excludeId, bool $rejectInternalEmail): void
    {
        if ($data['full_name'] === '') {
            throw new WriteException(self::NAME_REQUIRED, 'Full name is required.');
        }

        $email = $data['email'];
        if ($email === null) {
            return;
        }
        if (!EmailInspector::isValid($email)) {
            throw new WriteException(self::EMAIL_INVALID, 'Email is invalid.');
        }
        if ($rejectInternalEmail && str_contains(strtolower($email), 'unanime')) {
            throw new WriteException(self::INTERNAL_EMAIL, 'Internal company emails cannot be added as contacts.');
        }

        $duplicate = $excludeId === null
            ? $this->contacts->emailExists($email)
            : $this->contacts->emailTakenByOther($email, $excludeId);
        if ($duplicate) {
            throw new WriteException(self::EMAIL_DUPLICATE, 'Contact with this email already exists.', 409);
        }
    }

    private function transaction(callable $callback): mixed
    {
        try {
            return Database::transaction($callback);
        } catch (PDOException $exception) {
            if (Database::violatesConstraint($exception, 'uq_contacts_email')) {
                throw new WriteException(
                    self::EMAIL_DUPLICATE,
                    'Contact with this email already exists.',
                    409,
                    $exception
                );
            }
            throw $exception;
        }
    }

    private function nullableString(mixed $value): ?string
    {
        return Strings::nullable($value);
    }
}
