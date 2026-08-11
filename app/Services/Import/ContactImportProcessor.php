<?php

class ContactImportProcessor extends AbstractImportProcessor
{
    private ClientRepository $clients;
    private ContactWriteService $contactWriter;
    private ClientWriteService $clientWriter;
    private array $clientCache = [];

    public function __construct()
    {
        parent::__construct();
        $this->clients = new ClientRepository();
        $this->contactWriter = new ContactWriteService(
            $this->contacts,
            $this->entityTags,
            $this->customFields
        );
        $this->clientWriter = new ClientWriteService($this->clients);
    }

    public function process(array $mapped, array $raw, array $mapping, array $customFieldTypes): void
    {
        $data = [
            'full_name' => $mapped['full_name'] ?? '',
            'email' => $mapped['email'] ?? null,
            'phone' => $mapped['phone'] ?? null,
            'company' => $mapped['company'] ?? '',
        ];

        $clientId = $this->clientId(
            (string) ($mapped['client'] ?? ''),
            (string) ($mapped['sector'] ?? '')
        );
        $tagIds = $this->tagIds((string) ($mapped['tags'] ?? ''));
        [$customFields, $customValues] = $this->customFieldWriteData(
            'contact',
            $raw,
            $mapping,
            $customFieldTypes
        );
        $this->contactWriter->create(
            data: $data,
            tagIds: $tagIds,
            clientIds: $clientId === null ? [] : [$clientId],
            customFields: $customFields,
            customValues: $customValues,
            applyCustomFieldDefaults: false,
            checkEmailDns: false
        );
    }

    private function clientId(string $name, string $sector): ?int
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $key = $this->lower($name);
        if (isset($this->clientCache[$key])) {
            return $this->clientCache[$key];
        }

        $client = $this->clients->findByCommercialName($name);
        if ($client !== null) {
            return $this->clientCache[$key] = (int) $client['id'];
        }

        return $this->clientCache[$key] = $this->clientWriter->create([
            'commercial_name' => $name,
            'sector_id' => $this->sectorId($sector),
        ]);
    }

    private function lower(string $value): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
    }
}
