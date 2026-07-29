<?php

final class ContactWriteService
{
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
        bool $applyCustomFieldDefaults = true
    ): int {
        $data = $this->normalize($data);

        return Database::transaction(function () use (
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
        array $customValues = []
    ): void {
        $current = $this->contacts->find($id);
        if ($current === null) {
            throw new RuntimeException('Contact not found.');
        }

        $data = $this->normalize(array_replace($current, $changes));

        Database::transaction(function () use (
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

    private function normalize(array $data): array
    {
        return [
            'full_name' => trim((string) ($data['full_name'] ?? '')),
            'email' => $this->nullableString($data['email'] ?? null),
            'phone' => $this->nullableString($data['phone'] ?? null),
            'company' => trim((string) ($data['company'] ?? '')),
            'is_corporate_email' => isset($data['is_corporate_email'])
                ? (int) (bool) $data['is_corporate_email']
                : null,
            'email_status' => $this->nullableString($data['email_status'] ?? null),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }
}
