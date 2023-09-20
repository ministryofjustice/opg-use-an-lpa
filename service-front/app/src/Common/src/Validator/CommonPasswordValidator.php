<?php

namespace Common\Validator;

use Laminas\Validator\AbstractValidator;
use RuntimeException;

class CommonPasswordValidator extends AbstractValidator
{
    public const COMMON_PASSWORD = 'common';

    /**
     * @var string[]
     */
    protected array $messageTemplates = [
        self::COMMON_PASSWORD => 'Password is too common',
    ];


    const TMP_ROOT_PATH = '/tmp/';
    const PWNED_PW_URL = 'https://www.ncsc.gov.uk/static-assets/documents/PwnedPasswordsTop100k.txt';

    private string $filePathCommonPasswords;

    private string $pwnedPasswordsUrl;

    public function __construct()
    {
        parent::__construct();
        $this->filePathCommonPasswords = self::TMP_ROOT_PATH.'commonpasswords.txt';
        $this->pwnedPasswordsUrl = self::PWNED_PW_URL;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function isValid($value): bool
    {
        $isValid = true;
        $this->checkCommonPasswordsFileExists($this->filePathCommonPasswords);

        if ($this->passwordMatchesCommonPasswords($value, $this->filePathCommonPasswords)) {
            $this->error(self::COMMON_PASSWORD);
            $isValid = false;
        }

        return $isValid;
    }

    protected function passwordMatchesCommonPasswords(string $searchTerm, string $filePath): bool
    {
        $matches = [];
        $handle = @fopen($filePath, 'r');
        if ($handle && strlen($searchTerm) > 0) {
            while (!feof($handle)) {
                $buffer = fgets($handle);
                if (false !== strpos($buffer, $searchTerm)) {
                    $matches[] = $buffer;
                }
            }
            fclose($handle);
        }
        //show results:
        if (count($matches) > 0) {
            return true;
        } else {
            return false;
        }
    }

    protected function checkCommonPasswordsFileExists(string $filePath): void
    {
        if (file_exists($filePath) & (time() - filemtime($filePath) < 24 * 3600)) {
            return;
        } else {
            $fp = fopen($this->pwnedPasswordsUrl, 'r');
            if (false !== $fp) {
                $written = file_put_contents(
                    "$filePath",
                    $fp
                );
                if (false === $written) {
                    throw new RuntimeException(sprintf('Unable to download or write common password file to disk'));
                }
            }
        }
    }
}
