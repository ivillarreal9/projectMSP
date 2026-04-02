<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('msp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('label')->nullable();
            $table->boolean('is_secret')->default(false); // para enmascarar en UI
            $table->timestamps();
        });

        // Insertar valores por defecto desde .env si existen
        $defaults = [
            ['key' => 'azure_tenant_id',       'label' => 'Azure Tenant ID',         'is_secret' => false, 'value' => env('AZURE_TENANT_ID')],
            ['key' => 'azure_client_id',        'label' => 'Azure Client ID',         'is_secret' => false, 'value' => env('AZURE_CLIENT_ID')],
            ['key' => 'azure_client_secret',    'label' => 'Azure Client Secret',     'is_secret' => true,  'value' => env('AZURE_CLIENT_SECRET')],
            ['key' => 'sharepoint_site_url',    'label' => 'SharePoint Site URL',     'is_secret' => false, 'value' => env('SHAREPOINT_SITE_URL')],
            ['key' => 'sharepoint_folder_id',   'label' => 'SharePoint Folder ID',    'is_secret' => false, 'value' => env('SHAREPOINT_FOLDER_ID', '0162CR6XAYUKWCMRXHCBALS4LEZNKDGYO4')],
            ['key' => 'sharepoint_default_file','label' => 'Archivo por defecto',     'is_secret' => false, 'value' => env('SHAREPOINT_FILE', 'MSP_REPORT_CLIENTES_2026.xlsx')],
            ['key' => 'sendgrid_api_key',       'label' => 'SendGrid API Key',        'is_secret' => true,  'value' => env('SENDGRID_API_KEY')],
            ['key' => 'sendgrid_from_email',    'label' => 'SendGrid From Email',     'is_secret' => false, 'value' => env('SENDGRID_FROM_EMAIL', 'reportes@ovnicom.com')],
        ];

        foreach ($defaults as $setting) {
            \DB::table('msp_settings')->insert(array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('msp_settings');
    }
};
