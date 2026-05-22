<?php

namespace App\Http\Controllers;

use App\Models\Project;

class ProjectController extends Controller
{
    private const ID_MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];

    private const STATUS_UI = [
        'on-track'  => ['pill' => 'bg-emerald-100 text-emerald-700', 'dot' => 'bg-emerald-500', 'label' => 'Active'],
        'attention' => ['pill' => 'bg-amber-100 text-amber-700',     'dot' => 'bg-amber-500',   'label' => 'Needs Attention'],
        'critical'  => ['pill' => 'bg-rose-100 text-rose-700',       'dot' => 'bg-rose-500',    'label' => 'Critical'],
    ];

    private const DESC_MAP = [
        'AC' => 'Sales pipeline & customer dashboard internal untuk tim sales B2B PT Maju Jaya. Mencakup modul autentikasi, dashboard ringkas, dan project hub Kanban.',
        'BP' => 'Self-service portal untuk merchant onboarding & verifikasi dokumen end-to-end.',
        'GA' => 'API gateway terpusat untuk integrasi sistem internal & partner enterprise.',
        'DL' => 'Mobile + dashboard untuk monitoring armada & tracking pengiriman real-time.',
        'EX' => 'Internal tooling exchange data antar sistem legacy klien enterprise.',
        'ZN' => 'Customer-facing mobile app untuk loyalty program & point reward.',
        'OT' => 'Modul onboarding karyawan dengan e-signing & dokumen otomatis.',
        'KP' => 'Point-of-sale lightweight untuk SMB ritel dengan multi-cabang.',
    ];

    public function show(Project $project)
    {
        $project->load(['client', 'lead']);

        $statusUi = self::STATUS_UI[$project->status] ?? self::STATUS_UI['on-track'];

        $leadName     = $project->lead?->name;
        $leadInitials = $leadName ? $this->initials($leadName) : '—';

        $desc = self::DESC_MAP[$project->code] ?? 'Proyek demo internal Avatech untuk validasi sistem PMIS.';

        $due       = $project->due_at ? $this->formatDateId($project->due_at) : '—';
        $createdAt = $project->created_at ? $this->formatDateLongId($project->created_at) : '—';

        return view('projects.show', [
            'title'        => $project->name,
            'project'      => $project,
            'desc'         => $desc,
            'createdBy'    => 'Ahmad Rafiadly A.',
            'createdAt'    => $createdAt,
            'dueFormatted' => $due,
            'statusUi'     => $statusUi,
            'leadName'     => $leadName,
            'leadInitials' => $leadInitials,
        ]);
    }

    private function initials(string $fullName): string
    {
        $parts = array_values(array_filter(preg_split('/\s+/', trim($fullName)) ?: []));
        if (empty($parts)) {
            return '?';
        }
        if (count($parts) === 1) {
            return mb_strtoupper(mb_substr($parts[0], 0, 2));
        }
        return mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
    }

    private function formatDateId(\Illuminate\Support\Carbon $date): string
    {
        $month = self::ID_MONTHS[((int) $date->format('n')) - 1] ?? $date->format('M');
        return $date->format('d') . ' ' . $month;
    }

    private function formatDateLongId(\Illuminate\Support\Carbon $date): string
    {
        $month = self::ID_MONTHS[((int) $date->format('n')) - 1] ?? $date->format('M');
        return $date->format('d') . ' ' . $month . ' ' . $date->format('Y');
    }
}
