<?php

class ClientImportProcessor extends AbstractImportProcessor
{
    private ClientRepository $clients;
    private ClientWriteService $clientWriter;

    public function __construct()
    {
        parent::__construct();
        $this->clients = new ClientRepository();
        $this->clientWriter = new ClientWriteService(
            $this->clients,
            $this->entityTags,
            $this->customFields
        );
    }

    public function process(array $mapped, array $raw, array $mapping, array $customFieldTypes): void
    {
        $tagIds = $this->tagIds((string) ($mapped['tags'] ?? ''));
        $contactIds = $this->contactIds((string) ($mapped['contact'] ?? ''));
        [$customFields, $customValues] = $this->customFieldWriteData(
            'client',
            $raw,
            $mapping,
            $customFieldTypes
        );
        $this->clientWriter->create(
            data: [
                'commercial_name' => $mapped['commercial_name'] ?? '',
                'legal_name' => $mapped['legal_name'] ?? null,
                'cif' => $mapped['cif'] ?? null,
                'address' => $mapped['address'] ?? null,
                'postal_code' => $mapped['postal_code'] ?? null,
                'city' => $mapped['city'] ?? null,
                'province' => $mapped['province'] ?? null,
                'country' => $mapped['country'] ?? null,
                'sector_id' => $this->sectorId((string) ($mapped['sector'] ?? '')),
                'website' => $mapped['website'] ?? null,
                'notes' => $mapped['notes'] ?? null,
            ],
            tagIds: $tagIds,
            contactIds: $contactIds,
            customFields: $customFields,
            customValues: $customValues,
            applyCustomFieldDefaults: false
        );
    }
}
