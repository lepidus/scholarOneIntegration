<?php

namespace APP\plugins\generic\scholarOneIntegration\classes\migration;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use PKP\install\DowngradeNotSupportedException;
use APP\plugins\generic\scholarOneIntegration\classes\APIKeyEncryption;

class EncryptLegacyCredentials extends Migration
{
    private const PLUGIN_NAME_SETTINGS = 'scholaroneintegrationplugin';
    private const PLUGIN_CREDENTIALS_SETTINGS = [
        'clientKey',
        'accessKey',
        'secretKey'
    ];

    public function up(): void
    {
        $credentialSettings = $this->getCredentialSettings();

        if (!empty($credentialSettings)) {
            $encrypter = new APIKeyEncryption();

            foreach ($credentialSettings as $credentialSetting) {
                $credentialSetting = get_object_vars($credentialSetting);

                if ($encrypter->textIsEncrypted($credentialSetting['setting_value'])) {
                    continue;
                }

                $this->encryptCredential($credentialSetting);
            }
        }
    }

    public function down(): void
    {
        throw new DowngradeNotSupportedException();
    }

    private function getCredentialSettings()
    {
        return DB::table('plugin_settings')
            ->where('plugin_name', self::PLUGIN_NAME_SETTINGS)
            ->whereIn('setting_name', self::PLUGIN_CREDENTIALS_SETTINGS)
            ->get();
    }

    private function encryptCredential($credentialSetting)
    {
        $encrypter = new APIKeyEncryption();

        $settingValue = $this->extractSettingValue($credentialSetting['setting_value']);
        $encryptedSettingValue = $encrypter->encryptString($settingValue);

        DB::table('plugin_settings')
            ->where('context_id', $credentialSetting['context_id'])
            ->where('plugin_name', self::PLUGIN_NAME_SETTINGS)
            ->where('setting_name', $credentialSetting['setting_name'])
            ->update(['setting_value' => $encryptedSettingValue]);
    }

    private function extractSettingValue($settingValue)
    {
        $jwtParts = explode('.', $settingValue);
        if (count($jwtParts) == 3) {
            $header = json_decode(base64_decode($jwtParts[0]), true);
            if (!isset($header['alg']) || !isset($header['typ'])) {
                return $settingValue;
            }

            $payload = base64_decode($jwtParts[1]);
            return trim($payload, '"');
        }

        return $settingValue;
    }
}
