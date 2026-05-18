<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_PROVISIONING = 'provisioning';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'plan_id',
        'theme_id',
        'name',
        'slug',
        'subdomain',
        'fqdn',
        'primary_domain',
        'custom_domain_enabled',
        'hestia_username',
        'hestia_domain',

        'db_name',
        'db_user',
        'db_password',
        'hestia_password',
        'wp_admin_password',

        'wordpress_admin_url',
        'wordpress_admin_username',
        'wordpress_admin_email',

        'status',
        'billing_status',
        'suspension_reason',
        'provisioning_error',
        'provisioned_at',
        'suspended_at',
        'wordpress_sso_secret',

        'suspension_requested_at',
        'suspension_verified_at',
        'suspension_last_checked_at',
        'suspension_last_failed_at',
        'suspension_attempts',
        'suspension_last_error',
    ];

    protected array $temporaryProvisioningData = [];

    protected function casts(): array
    {
        return [
            'custom_domain_enabled' => 'boolean',
            'provisioned_at' => 'datetime',
            'suspended_at' => 'datetime',

            'suspension_requested_at' => 'datetime',
            'suspension_verified_at' => 'datetime',
            'suspension_last_checked_at' => 'datetime',
            'suspension_last_failed_at' => 'datetime',
            'suspension_attempts' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function theme()
    {
        return $this->belongsTo(Theme::class);
    }

    public function domains()
    {
        return $this->hasMany(SiteDomain::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }

    public function provisioningLogs()
    {
        return $this->hasMany(ProvisioningLog::class);
    }

    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function isProvisionable(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING_PAYMENT,
            self::STATUS_FAILED,
        ], true);
    }

    public function setTemporaryProvisioningData(array $data): void
    {
        $this->temporaryProvisioningData = $data;
    }

    public function __get($key)
    {
        if (array_key_exists($key, $this->temporaryProvisioningData)) {
            return $this->temporaryProvisioningData[$key];
        }

        return parent::__get($key);
    }
}