<?php

class ContactImportProcessor extends AbstractImportProcessor
{
    private ClientRepository $clients;
    private ContactWriteService $contactWriter;
    private ClientWriteService $clientWriter;
    private array $clientCache = [];
    private array $emailCache = [];

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
        $fullName = trim((string) ($mapped['full_name'] ?? ''));
        $email = trim((string) ($mapped['email'] ?? ''));

        if ($fullName === '') {
            throw new ImportRowException('Full name is required.');
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ImportRowException('Invalid email: ' . $email);
        }
        if ($email !== '' && $this->emailExists($email)) {
            throw new ImportRowException('Duplicate email: ' . $email, 'skipped');
        }

        $data = [
            'full_name' => $fullName,
            'email' => $this->nullable($email),
            'phone' => $this->nullable($mapped['phone'] ?? null),
            'company' => trim((string) ($mapped['company'] ?? '')),
        ];
        $data = array_merge($data, EmailInspector::inspect($data['email'], false));

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
        $contactId = $this->contactWriter->create(
            data: $data,
            tagIds: $tagIds,
            clientIds: $clientId === null ? [] : [$clientId],
            customFields: $customFields,
            customValues: $customValues,
            applyCustomFieldDefaults: false
        );

        if ($tagIds !== [] && $clientId !== null) {
            $this->entityTags->add('client', [$clientId], $tagIds);
        }

        if ($email !== '') {
            $this->emailCache[$this->lower($email)] = true;
        }

    }

    private function emailExists(string $email): bool
    {
        $key = $this->lower($email);
        if (isset($this->emailCache[$key])) {
            return true;
        }
        return $this->contacts->emailExists($email);
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
