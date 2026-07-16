<?php

class ClientImportProcessor extends AbstractImportProcessor
{
    private ClientRepository $clients;
    private array $clientCache = [];

    public function __construct()
    {
        parent::__construct();
        $this->clients = new ClientRepository();
    }

    public function process(array $mapped, array $raw, array $mapping, array $customFieldTypes): void
    {
        $name = trim((string) ($mapped['commercial_name'] ?? ''));
        if ($name === '') {
            throw new ImportRowException('Commercial name is required.');
        }

        $key = $this->lower($name);
        if (isset($this->clientCache[$key]) || $this->clients->findByCommercialName($name) !== null) {
            throw new ImportRowException('Duplicate client: ' . $name, 'skipped');
        }

        $clientId = $this->clients->create([
            'commercial_name' => $name,
            'legal_name' => $this->nullable($mapped['legal_name'] ?? null),
            'cif' => $this->nullable($mapped['cif'] ?? null),
            'address' => $this->nullable($mapped['address'] ?? null),
            'postal_code' => $this->nullable($mapped['postal_code'] ?? null),
            'city' => $this->nullable($mapped['city'] ?? null),
            'province' => $this->nullable($mapped['province'] ?? null),
            'country' => $this->nullable($mapped['country'] ?? null),
            'sector_id' => $this->sectorId((string) ($mapped['sector'] ?? '')),
            'website' => $this->nullable($mapped['website'] ?? null),
            'notes' => $this->nullable($mapped['notes'] ?? null),
            'is_web_connected' => 0,
            'is_active' => 1,
        ]);

        $tagIds = $this->tagIds((string) ($mapped['tags'] ?? ''));
        if ($tagIds !== []) {
            $this->entityTags->sync('client', $clientId, $tagIds);
        }

        $contactIds = $this->contactIds((string) ($mapped['contact'] ?? ''));
        if ($contactIds !== []) {
            $this->clients->addContacts($clientId, $contactIds);
        }

        $this->saveCustomFields('client', $clientId, $raw, $mapping, $customFieldTypes);
        $this->clientCache[$key] = $clientId;

    }

    private function lower(string $value): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
    }
}
