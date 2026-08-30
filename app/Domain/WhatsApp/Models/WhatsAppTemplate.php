<?php

namespace App\Domain\WhatsApp\Models;

use App\Domain\Shared\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class WhatsAppTemplate extends Model
{
    use BelongsToTenant, HasFactory, LogsActivity;

    // Eloquent's default guess ("whats_app_templates") splits "WhatsApp" into two words —
    // wrong for a proper-noun brand name, and inconsistent with the whatsapp_* column naming
    // already used everywhere else in this domain.
    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'spa_id', 'meta_template_id', 'name', 'category', 'language',
        'header_text', 'body_text', 'footer_text', 'buttons', 'variable_samples',
        'status', 'rejected_reason',
    ];

    protected function casts(): array
    {
        return [
            'buttons' => 'array',
            'variable_samples' => 'array',
        ];
    }

    public function campaigns(): HasMany
    {
        // Explicit FK — Eloquent's default guess from this class name ("whats_app_template_id")
        // splits "WhatsApp" into two words, which doesn't match the actual column.
        return $this->hasMany(WhatsAppCampaign::class, 'whatsapp_template_id');
    }

    /**
     * How many {{1}}, {{2}}... placeholders this template's body actually uses — drives how
     * many variable-mapping rows a campaign's Create form shows.
     */
    public function variableCount(): int
    {
        preg_match_all('/\{\{(\d+)\}\}/', $this->body_text, $matches);

        return $matches[1] === [] ? 0 : (int) max($matches[1]);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
