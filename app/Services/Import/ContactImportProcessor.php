<?php

class ContactImportProcessor extends AbstractImportProcessor
{
    private ContactRepository $contacts;
    private ClientRepository $clients;
    private array $clientCache = [];
    private array $emailCache = [];

    public function __construct()
    {
        parent::__construct();
        $this->contacts = new ContactRepository();
        $this->clients = new ClientRepository();
    }

    public function process(array $mapped, array $raw, array $mapping, array $customFieldTypes): array
    {
        $firstName = trim((string) ($mapped['first_name'] ?? ''));
        $email = trim((string) ($mapped['email'] ?? ''));

        if ($firstName === '') {
            throw new ImportRowException('First name is required.');
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ImportRowException('Invalid email: ' . $email);
        }
        if ($email !== '' && $this->emailExists($email)) {
            throw new ImportRowException('Duplicate email: ' . $email, 'skipped');
        }

        $contactId = $this->contacts->create([
            'first_name' => $firstName,
            'last_name' => $this->nullable($mapped['last_name'] ?? null),
            'email' => $this->nullable($email),
            'phone' => $this->nullable($mapped['phone'] ?? null),
            'is_company' => $this->boolValue((string) ($mapped['is_company'] ?? '')),
        ]);

        $clientId = $this->clientId(
            (string) ($mapped['client'] ?? ''),
            (string) ($mapped['sector'] ?? '')
        );
        $tagIds = $this->tagIds((string) ($mapped['tags'] ?? ''));

        if ($clientId !== null) {
            $this->contacts->syncClients($contactId, [$clientId]);
        }
        if ($tagIds !== []) {
            $this->contacts->syncTags($contactId, $tagIds);
            if ($clientId !== null) {
                $this->clients->addTags($clientId, $tagIds);
            }
        }

        $this->saveCustomFields('contact', $contactId, $raw, $mapping, $customFieldTypes);
        if ($email !== '') {
            $this->emailCache[$this->lower($email)] = true;
        }

        return ['contact_id' => $contactId, 'client_id' => $clientId];
    }

    public function resetCaches(): void
    {
        parent::resetCaches();
        $this->clientCache = [];
        $this->emailCache = [];
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

        return $this->clientCache[$key] = $this->clients->create([
            'commercial_name' => $name,
            'legal_name' => null,
            'cif' => null,
            'address' => null,
            'postal_code' => null,
            'city' => null,
            'province' => null,
            'country' => null,
            'sector_id' => $this->sectorId($sector),
            'website' => null,
            'notes' => null,
        ]);
    }

    private function lower(string $value): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
    }
}
