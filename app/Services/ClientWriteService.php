<?php

final class ClientWriteService
{
    private ClientRepository $clients;
    private EntityTagRepository $entityTags;
    private CustomFieldRepository $customFields;

    public function __construct(
        ?ClientRepository $clients = null,
        ?EntityTagRepository $entityTags = null,
        ?CustomFieldRepository $customFields = null
    ) {
        $this->clients = $clients ?? new ClientRepository();
        $this->entityTags = $entityTags ?? new EntityTagRepository();
        $this->customFields = $customFields ?? new CustomFieldRepository();
    }

    public function create(
        array $data,
        array $tagIds = [],
        array $contactIds = [],
        array $customFields = [],
        array $customValues = [],
        bool $applyCustomFieldDefaults = true
    ): int {
        $data = $this->normalize($data);

        return Database::transaction(function () use (
            $data,
            $tagIds,
            $contactIds,
            $customFields,
            $customValues,
            $applyCustomFieldDefaults
        ): int {
            $id = $this->clients->create($data);
            $this->entityTags->sync('client', $id, $tagIds);
            $this->clients->addContacts($id, $contactIds);

            if ($customFields !== []) {
                $this->customFields->saveValues(
                    'client',
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
     * values. An explicitly supplied empty tag array clears all tags.
     */
    public function update(
        int $id,
        array $changes,
        ?array $tagIds = null,
        ?array $customFields = null,
        array $customValues = []
    ): void {
        $current = $this->clients->find($id);
        if ($current === null) {
            throw new RuntimeException('Client not found.');
        }

        $data = $this->normalize(array_replace($current, $changes));

        Database::transaction(function () use ($id, $data, $tagIds, $customFields, $customValues): void {
            $this->clients->update($id, $data);

            if ($tagIds !== null) {
                $this->entityTags->sync('client', $id, $tagIds);
            }
            if ($customFields !== null && $customFields !== []) {
                $this->customFields->saveValues('client', $id, $customFields, $customValues);
            }
        });
    }

    private function normalize(array $data): array
    {
        $sectorId = (int) ($data['sector_id'] ?? 0);

        return [
            'commercial_name' => trim((string) ($data['commercial_name'] ?? '')),
            'legal_name' => $this->nullableString($data['legal_name'] ?? null),
            'cif' => $this->nullableString($data['cif'] ?? null),
            'address' => $this->nullableString($data['address'] ?? null),
            'postal_code' => $this->nullableString($data['postal_code'] ?? null),
            'city' => $this->nullableString($data['city'] ?? null),
            'province' => $this->nullableString($data['province'] ?? null),
            'country' => $this->nullableString($data['country'] ?? null),
            'sector_id' => $sectorId > 0 ? $sectorId : null,
            'website' => $this->nullableString($data['website'] ?? null),
            'notes' => $this->nullableString($data['notes'] ?? null),
            'is_web_connected' => array_key_exists('is_web_connected', $data)
                ? (int) (bool) $data['is_web_connected']
                : 0,
            'is_active' => array_key_exists('is_active', $data)
                ? (int) (bool) $data['is_active']
                : 1,
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }
}
