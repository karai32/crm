<?php

class WeeklyReportService
{
    public function collect(): array
    {
        $pdo  = Database::connect();
        $to   = date('Y-m-d H:i:s');
        $from = date('Y-m-d H:i:s', strtotime('-7 days'));

        return [
            'period_from'   => $from,
            'period_to'     => $to,
            'new_contacts'  => $this->queryNewContacts($pdo, $from, $to),
            'top_clients'   => $this->queryTopClients($pdo, $from, $to),
            'new_clients'   => $this->queryNewClients($pdo, $from, $to),
            'web_connected' => $this->queryWebConnected($pdo, $from, $to),
            'deactivated'   => $this->queryDeactivated($pdo, $from, $to),
        ];
    }

    private function queryNewContacts(PDO $pdo, string $from, string $to): array
    {
        $stmt = $pdo->prepare("
            SELECT full_name, email, company, created_at
            FROM contacts
            WHERE created_at >= :from AND created_at < :to
              AND full_name NOT LIKE '%http%'
              AND full_name NOT LIKE '%://%'
              AND full_name NOT LIKE '%www.%'
              AND LENGTH(full_name) < 150
            ORDER BY created_at DESC
        ");
        $stmt->execute(compact('from', 'to'));
        return $stmt->fetchAll();
    }

    private function queryTopClients(PDO $pdo, string $from, string $to): array
    {
        $stmt = $pdo->prepare('
            SELECT c.commercial_name, COUNT(cc.contact_id) AS new_contacts_count
            FROM clients c
            INNER JOIN client_contacts cc ON cc.client_id = c.id
            INNER JOIN contacts ct        ON ct.id = cc.contact_id
            WHERE ct.created_at >= :from AND ct.created_at < :to
            GROUP BY c.id, c.commercial_name
            ORDER BY new_contacts_count DESC
            LIMIT 10
        ');
        $stmt->execute(compact('from', 'to'));
        return $stmt->fetchAll();
    }

    private function queryNewClients(PDO $pdo, string $from, string $to): array
    {
        $stmt = $pdo->prepare('
            SELECT commercial_name, legal_name, created_at
            FROM clients
            WHERE created_at >= :from AND created_at < :to
            ORDER BY created_at DESC
        ');
        $stmt->execute(compact('from', 'to'));
        return $stmt->fetchAll();
    }

    private function queryWebConnected(PDO $pdo, string $from, string $to): array
    {
        $stmt = $pdo->prepare('
            SELECT commercial_name, is_web_connected_date
            FROM clients
            WHERE is_web_connected = 1
              AND is_web_connected_date >= :from
              AND is_web_connected_date < :to
            ORDER BY is_web_connected_date DESC
        ');
        $stmt->execute(compact('from', 'to'));
        return $stmt->fetchAll();
    }

    private function queryDeactivated(PDO $pdo, string $from, string $to): array
    {
        $stmt = $pdo->prepare('
            SELECT commercial_name, is_active_date
            FROM clients
            WHERE is_active = 0
              AND is_active_date >= :from
              AND is_active_date < :to
            ORDER BY is_active_date DESC
        ');
        $stmt->execute(compact('from', 'to'));
        return $stmt->fetchAll();
    }

    public function buildHtml(array $data): string
    {
        $from = date('d/m/Y', strtotime($data['period_from']));
        $to   = date('d/m/Y', strtotime($data['period_to']));
        $now  = date('d/m/Y \a \l\a\s H:i');

        $nc  = $data['new_contacts'];
        $tc  = $data['top_clients'];
        $ncl = $data['new_clients'];
        $wc  = $data['web_connected'];
        $de  = $data['deactivated'];

        $body = $this->buildSummaryRow(count($nc), count($ncl), count($wc), count($de));

        // Nuevos contactos
        if (count($nc) > 0) {
            $rows = '';
            foreach ($nc as $r) {
                $rows .= '<tr>'
                    . '<td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">' . $this->e($r['full_name']) . '</td>'
                    . '<td style="padding:8px 12px;border-bottom:1px solid #f1f5f9;color:#64748b">' . $this->e($r['email'] ?? '—') . '</td>'
                    . '<td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">' . $this->e($r['company']) . '</td>'
                    . '<td style="padding:8px 12px;border-bottom:1px solid #f1f5f9;color:#64748b;white-space:nowrap">' . date('d/m/Y', strtotime($r['created_at'])) . '</td>'
                    . '</tr>';
            }
            $body .= $this->buildSection(
                'Nuevos contactos (' . count($nc) . ')',
                $this->buildTable(['Nombre', 'Email', 'Empresa', 'Fecha'], $rows)
            );
        } else {
            $body .= $this->buildSection('Nuevos contactos (0)', $this->emptyMsg('No hay nuevos contactos esta semana.'));
        }

        // Top clientes
        if (count($tc) > 0) {
            $rows = '';
            foreach ($tc as $r) {
                $rows .= '<tr>'
                    . '<td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">' . $this->e($r['commercial_name']) . '</td>'
                    . '<td style="padding:8px 12px;border-bottom:1px solid #f1f5f9;text-align:center;font-weight:700;color:#1e40af">' . (int) $r['new_contacts_count'] . '</td>'
                    . '</tr>';
            }
            $body .= $this->buildSection(
                'Top 10 clientes con más contactos nuevos',
                $this->buildTable(['Cliente', 'Nuevos contactos'], $rows)
            );
        } else {
            $body .= $this->buildSection('Top 10 clientes con más contactos nuevos', $this->emptyMsg('Sin datos esta semana.'));
        }

        // Nuevos clientes
        if (count($ncl) > 0) {
            $rows = '';
            foreach ($ncl as $r) {
                $rows .= '<tr>'
                    . '<td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">' . $this->e($r['commercial_name']) . '</td>'
                    . '<td style="padding:8px 12px;border-bottom:1px solid #f1f5f9;color:#64748b">' . $this->e($r['legal_name'] ?? '—') . '</td>'
                    . '<td style="padding:8px 12px;border-bottom:1px solid #f1f5f9;color:#64748b;white-space:nowrap">' . date('d/m/Y', strtotime($r['created_at'])) . '</td>'
                    . '</tr>';
            }
            $body .= $this->buildSection(
                'Nuevos clientes (' . count($ncl) . ')',
                $this->buildTable(['Cliente', 'Razón social', 'Fecha'], $rows)
            );
        } else {
            $body .= $this->buildSection('Nuevos clientes (0)', $this->emptyMsg('No hay nuevos clientes esta semana.'));
        }

        // Conectados a la plataforma
        if (count($wc) > 0) {
            $rows = '';
            foreach ($wc as $r) {
                $rows .= '<tr>'
                    . '<td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">' . $this->e($r['commercial_name']) . '</td>'
                    . '<td style="padding:8px 12px;border-bottom:1px solid #f1f5f9;color:#64748b;white-space:nowrap">' . date('d/m/Y', strtotime($r['is_web_connected_date'])) . '</td>'
                    . '</tr>';
            }
            $body .= $this->buildSection(
                'Clientes conectados a la plataforma (' . count($wc) . ')',
                $this->buildTable(['Cliente', 'Fecha de conexión'], $rows),
                '#22c55e'
            );
        } else {
            $body .= $this->buildSection('Clientes conectados a la plataforma (0)', $this->emptyMsg('Ningún cliente conectado esta semana.'), '#22c55e');
        }

        // Desactivados
        if (count($de) > 0) {
            $rows = '';
            foreach ($de as $r) {
                $rows .= '<tr>'
                    . '<td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">' . $this->e($r['commercial_name']) . '</td>'
                    . '<td style="padding:8px 12px;border-bottom:1px solid #f1f5f9;color:#64748b;white-space:nowrap">' . date('d/m/Y', strtotime($r['is_active_date'])) . '</td>'
                    . '</tr>';
            }
            $body .= $this->buildSection(
                'Clientes desactivados (' . count($de) . ')',
                $this->buildTable(['Cliente', 'Fecha de desactivación'], $rows),
                '#dc2626'
            );
        } else {
            $body .= $this->buildSection('Clientes desactivados (0)', $this->emptyMsg('Ningún cliente desactivado esta semana.'), '#dc2626');
        }

        return '<!DOCTYPE html><html lang="es"><head>'
            . '<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>Informe Semanal CRM</title></head>'
            . '<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#1e293b">'
            . '<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#f1f5f9;padding:32px 16px">'
            . '<tr><td align="center">'
            . '<table width="600" cellpadding="0" cellspacing="0" role="presentation" style="max-width:600px;width:100%">'
            . '<tr><td style="background:#1e40af;padding:32px;border-radius:8px 8px 0 0;text-align:center">'
            . '<h1 style="color:#fff;margin:0 0 8px;font-size:22px;font-weight:700">Informe Semanal CRM</h1>'
            . '<p style="color:#bfdbfe;margin:0;font-size:14px">Del ' . $from . ' al ' . $to . '</p>'
            . '</td></tr>'
            . '<tr><td style="background:#fff;padding:32px;border-radius:0 0 8px 8px">' . $body . '</td></tr>'
            . '<tr><td style="padding:20px 16px;text-align:center;color:#94a3b8;font-size:12px">'
            . 'Generado automáticamente el ' . $now
            . '</td></tr>'
            . '</table></td></tr></table>'
            . '</body></html>';
    }

    public function buildText(array $data): string
    {
        $from = date('d/m/Y', strtotime($data['period_from']));
        $to   = date('d/m/Y', strtotime($data['period_to']));

        $lines   = [];
        $lines[] = "INFORME SEMANAL CRM — Del $from al $to";
        $lines[] = str_repeat('=', 50);
        $lines[] = '';

        $sections = [
            ['title' => 'NUEVOS CONTACTOS', 'items' => $data['new_contacts'], 'key' => 'full_name'],
            ['title' => 'TOP CLIENTES CON MÁS CONTACTOS', 'items' => $data['top_clients'], 'key' => 'commercial_name'],
            ['title' => 'NUEVOS CLIENTES', 'items' => $data['new_clients'], 'key' => 'commercial_name'],
            ['title' => 'CONECTADOS A LA PLATAFORMA', 'items' => $data['web_connected'], 'key' => 'commercial_name'],
            ['title' => 'DESACTIVADOS', 'items' => $data['deactivated'], 'key' => 'commercial_name'],
        ];

        foreach ($sections as $s) {
            $lines[] = $s['title'] . ' (' . count($s['items']) . ')';
            $lines[] = str_repeat('-', 30);
            if (count($s['items']) > 0) {
                foreach ($s['items'] as $r) {
                    $lines[] = '• ' . $this->sanitize($r[$s['key']]);
                }
            } else {
                $lines[] = 'Sin actividad esta semana.';
            }
            $lines[] = '';
        }

        $lines[] = 'Generado el ' . date('d/m/Y H:i');

        return implode("\n", $lines);
    }

    private function buildSummaryRow(int $contacts, int $clients, int $connected, int $deact): string
    {
        $cell = static fn(int $n, string $label, string $bg) =>
            '<td style="width:25%;text-align:center;padding:6px">'
            . '<div style="background:' . $bg . ';border-radius:8px;padding:16px 4px">'
            . '<div style="font-size:28px;font-weight:700;color:#1e293b">' . $n . '</div>'
            . '<div style="font-size:11px;color:#64748b;margin-top:2px;line-height:1.3">' . $label . '</div>'
            . '</div></td>';

        return '<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:32px"><tr>'
            . $cell($contacts, 'Nuevos<br>contactos', '#eff6ff')
            . $cell($clients, 'Nuevos<br>clientes', '#f0fdf4')
            . $cell($connected, 'Conectados<br>plataforma', '#fefce8')
            . $cell($deact, 'Clientes<br>desactivados', '#fff1f2')
            . '</tr></table>';
    }

    private function buildSection(string $title, string $content, string $borderColor = '#3b82f6'): string
    {
        return '<div style="margin-bottom:28px">'
            . '<h3 style="margin:0 0 12px;font-size:14px;font-weight:700;color:#1e293b;padding-left:10px;border-left:3px solid ' . $borderColor . '">'
            . $this->e($title)
            . '</h3>'
            . $content
            . '</div>';
    }

    private function buildTable(array $headers, string $bodyRows): string
    {
        $ths = '';
        foreach ($headers as $h) {
            $ths .= '<th style="padding:8px 12px;text-align:left;background:#f8fafc;font-size:12px;font-weight:600;color:#475569;border-bottom:1px solid #e2e8f0">'
                . $this->e($h) . '</th>';
        }

        return '<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0;border-radius:6px;font-size:13px">'
            . '<thead><tr>' . $ths . '</tr></thead>'
            . '<tbody>' . $bodyRows . '</tbody>'
            . '</table>';
    }

    private function emptyMsg(string $msg): string
    {
        return '<p style="color:#94a3b8;font-size:13px;margin:0;font-style:italic">' . $this->e($msg) . '</p>';
    }

    private function sanitize(string $str, int $maxLen = 120): string
    {
        $str = preg_replace('/https?:\/\/\S+/i', '', $str);
        $str = preg_replace('/www\.\S+/i', '', $str);
        $str = strip_tags($str);
        $str = preg_replace('/\s+/', ' ', $str);
        return mb_substr(trim($str), 0, $maxLen);
    }

    private function e(string $str): string
    {
        return htmlspecialchars($this->sanitize($str), ENT_QUOTES, 'UTF-8');
    }
}
