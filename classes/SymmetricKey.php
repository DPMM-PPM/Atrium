<?php

abstract class SymmetricKey
{
    const MODE_CTR = -1;
    const MODE_ECB = 1;
    const MODE_CBC = 2;
    const MODE_CFB = 3;
    const MODE_CFB8 = 7;
    const MODE_OFB8 = 8;
    const MODE_OFB = 4;
    const MODE_GCM = 5;
    const MODE_STREAM = 6;
    const MODE_MAP = [
        'ctr'    => self::MODE_CTR,
        'ecb'    => self::MODE_ECB,
        'cbc'    => self::MODE_CBC,
        'cfb'    => self::MODE_CFB,
        'cfb8'   => self::MODE_CFB8,
        'ofb'    => self::MODE_OFB,
        'ofb8'   => self::MODE_OFB8,
        'gcm'    => self::MODE_GCM,
        'stream' => self::MODE_STREAM
    ];

    const ENGINE_INTERNAL = 1;
    const ENGINE_EVAL = 2;
    const ENGINE_MCRYPT = 3;
    const ENGINE_OPENSSL = 4;
    const ENGINE_LIBSODIUM = 5;
    const ENGINE_OPENSSL_GCM = 6;
    const ENGINE_MAP = [
        self::ENGINE_INTERNAL    => 'PHP',
        self::ENGINE_EVAL        => 'Eval',
        self::ENGINE_MCRYPT      => 'mcrypt',
        self::ENGINE_OPENSSL     => 'OpenSSL',
        self::ENGINE_LIBSODIUM   => 'libsodium',
        self::ENGINE_OPENSSL_GCM => 'OpenSSL (GCM)'
    ];

    protected $mode;
    protected $block_size = 16;
    protected $key = false;
    protected $hKey = false;
    protected $iv = false;
    protected $encryptIV;
    protected $decryptIV;
    protected $continuousBuffer = false;
    protected $enbuffer;
    protected $debuffer;
    private $enmcrypt;
    private $demcrypt;
    private $enchanged = true;
    private $dechanged = true;
    private $ecb;
    protected $cfb_init_len = 600;
    protected $changed = true;
    protected $nonIVChanged = true;
    private $padding = true;
    private $paddable = false;
    protected $engine;
    private $preferredEngine;
    protected $cipher_name_mcrypt;
    protected $cipher_name_openssl;
    protected $cipher_name_openssl_ecb;
    private $password_default_salt = 'phpseclib/salt';
    protected $inline_crypt;
    private $openssl_emulate_ctr = false;
    private $skip_key_adjustment = false;
    protected $explicit_key_length = false;
    private $h;
    protected $aad = '';
    protected $newtag = false;
    protected $oldtag = false;
    private static $gcmField;
    private static $poly1305Field;
    protected static $use_reg_intval;
    protected $poly1305Key;
    protected $usePoly1305 = false;
    private $origIV = false;
    protected $nonce = false;
    
    public function __construct($mode)
    {
        global $lng;
        $mode = strtolower($mode);
        
        $map = self::MODE_MAP;
        if (!isset($map[$mode])) {
            
            throw new ilAtriumException($lng->txt('rep_robj_xatr_xatr_no_valid_mode'));
        }

        $mode = self::MODE_MAP[$mode];

        switch ($mode) {
            case self::MODE_ECB:
            case self::MODE_CBC:
                $this->paddable = true;
                break;
            default:
                throw new ilAtriumException('rep_robj_xatr_xatr_no_valid_mode');
        }

        $this->mode = $mode;

        static::initialize_static_variables();
    }

    protected static function initialize_static_variables()
    {
        if (!isset(self::$use_reg_intval)) {
            switch (true) {
                case (PHP_OS & "\xDF\xDF\xDF") === 'WIN':
                case (php_uname('m') & "\xDF\xDF\xDF") != 'ARM':
                case defined('PHP_INT_SIZE') && PHP_INT_SIZE == 8:
                    self::$use_reg_intval = true;
                    break;
                case (php_uname('m') & "\xDF\xDF\xDF") == 'ARM':
                    switch (true) {
                        case PHP_VERSION_ID >= 70000 && PHP_VERSION_ID <= 70123:
                        case PHP_VERSION_ID >= 70200 && PHP_VERSION_ID <= 70211:
                            self::$use_reg_intval = false;
                            break;
                        default:
                            self::$use_reg_intval = true;
                    }
            }
        }
    }

    public function usesNonce()
    {
        return $this->mode == self::MODE_GCM;
    }

    public function getKeyLength()
    {
        return $this->key_length << 3;
    }

    public function getBlockLength()
    {
        return $this->block_size << 3;
    }

    public function getBlockLengthInBytes()
    {
        return $this->block_size;
    }

    public function setKey($key)
    {
        global $lng;
        
        if ($this->explicit_key_length !== false && strlen($key) != $this->explicit_key_length) {
            throw new ilAtriumException(sprintf($lng->txt("rep_robj_xatr_key_length_setting"),
                $this->explicit_key_length,
                strlen($key)
            ));
        }
        $this->key = $key;
        $this->key_length = strlen($key);
        $this->setEngine();
    }
   
    public function decrypt($ciphertext)
    {
        global $lng;
        if ($this->paddable && strlen($ciphertext) % $this->block_size) {
            throw new ilAtriumException(sprintf($lng->txt(
                "rep_robj_xatr_invalid_cipher_lenght"),
                strlen($ciphertext),
                $this->block_size
            ));
        }
        $this->setup();

        if ($this->engine === self::ENGINE_MCRYPT) {
            set_error_handler(function () {
            });
            $block_size = $this->block_size;
            if ($this->dechanged) {
                mcrypt_generic_init($this->demcrypt, $this->key, $this->getIV($this->decryptIV));
                $this->dechanged = false;
            }

            if ($this->mode == self::MODE_CFB && $this->continuousBuffer) {
                $iv = &$this->decryptIV;
                $pos = &$this->debuffer['pos'];
                $len = strlen($ciphertext);
                $plaintext = '';
                $i = 0;
                if ($pos) {
                    $orig_pos = $pos;
                    $max = $block_size - $pos;
                    if ($len >= $max) {
                        $i = $max;
                        $len -= $max;
                        $pos = 0;
                    } else {
                        $i = $len;
                        $pos += $len;
                        $len = 0;
                    }
                    $plaintext = substr($iv, $orig_pos) ^ $ciphertext;
                    $iv = substr_replace($iv, substr($ciphertext, 0, $i), $orig_pos, $i);
                }
                if ($len >= $block_size) {
                    $cb = substr($ciphertext, $i, $len - $len % $block_size);
                    $plaintext .= mcrypt_generic($this->ecb, $iv . $cb) ^ $cb;
                    $iv = substr($cb, -$block_size);
                    $len %= $block_size;
                }
                if ($len) {
                    $iv = mcrypt_generic($this->ecb, $iv);
                    $plaintext .= $iv ^ substr($ciphertext, -$len);
                    $iv = substr_replace($iv, substr($ciphertext, -$len), 0, $len);
                    $pos = $len;
                }

                restore_error_handler();

                return $plaintext;
            }

            $plaintext = mdecrypt_generic($this->demcrypt, $ciphertext);

            if (!$this->continuousBuffer) {
                mcrypt_generic_init($this->demcrypt, $this->key, $this->getIV($this->decryptIV));
            }

            restore_error_handler();

            return $this->paddable ? $this->unpad($plaintext) : $plaintext;
        }

        $block_size = $this->block_size;

        $buffer = &$this->debuffer;
        $plaintext = '';
        switch ($this->mode) {
            case self::MODE_ECB:
                for ($i = 0; $i < strlen($ciphertext); $i += $block_size) {
                    $plaintext .= $this->decryptBlock(substr($ciphertext, $i, $block_size));
                }
                break;
        }
        return $this->paddable ? $this->unpad($plaintext) : $plaintext;
    }

    public function enablePadding()
    {
        $this->padding = true;
    }

    public function disablePadding()
    {
        $this->padding = false;
    }

    protected function isValidEngineHelper($engine)
    {
        switch ($engine) {
            case self::ENGINE_MCRYPT:
                set_error_handler(function () {
                });
                $result = $this->cipher_name_mcrypt &&
                          extension_loaded('mcrypt') &&
                          in_array($this->cipher_name_mcrypt, mcrypt_list_algorithms());
                restore_error_handler();
                return $result;
        }
        return false;
    }

    protected function setEngine()
    {
        $this->engine = null;

        $candidateEngines = [
            self::ENGINE_LIBSODIUM,
            self::ENGINE_OPENSSL_GCM,
            self::ENGINE_OPENSSL,
            self::ENGINE_MCRYPT,
            self::ENGINE_EVAL
        ];
        if (isset($this->preferredEngine)) {
            $temp = [$this->preferredEngine];
            $candidateEngines = array_merge(
                $temp,
                array_diff($candidateEngines, $temp)
            );
        }
        foreach ($candidateEngines as $engine) {
            if ($this->isValidEngineHelper($engine)) {
                $this->engine = $engine;
                break;
            }
        }
        if (!$this->engine) {
            $this->engine = self::ENGINE_INTERNAL;
        }

        if ($this->engine != self::ENGINE_MCRYPT && $this->enmcrypt) {
            set_error_handler(function () {
            });
            mcrypt_module_close($this->enmcrypt);
            mcrypt_module_close($this->demcrypt);
            $this->enmcrypt = null;
            $this->demcrypt = null;

            if ($this->ecb) {
                mcrypt_module_close($this->ecb);
                $this->ecb = null;
            }
            restore_error_handler();
        }

        $this->changed = $this->nonIVChanged = true;
    }

    abstract protected function decryptBlock($in);

    protected function setup()
    {
        global $lng;
        if (!$this->changed) {
            return;
        }
        
        $this->changed = false;

        if ($this->usePoly1305 && !isset($this->poly1305Key) && method_exists($this, 'createPoly1305Key')) {
            $this->createPoly1305Key();
        }

        $this->enbuffer = $this->debuffer = ['ciphertext' => '', 'xor' => '', 'pos' => 0, 'enmcrypt_init' => true];
 
        if ($this->usesNonce()) {
            if ($this->nonce === false) {
                throw new ilAtriumException($lng->txt('rep_robj_xatr_no_nonce_defined'));
            }
            if ($this->mode == self::MODE_GCM && !in_array($this->engine, [self::ENGINE_LIBSODIUM, self::ENGINE_OPENSSL_GCM])) {
                $this->setupGCM();
            }
        } else {
            $this->iv = $this->origIV;
        }

        if ($this->iv === false && !in_array($this->mode, [self::MODE_STREAM, self::MODE_ECB])) {
            if ($this->mode != self::MODE_GCM || !in_array($this->engine, [self::ENGINE_LIBSODIUM, self::ENGINE_OPENSSL_GCM])) {
                throw new ilAtriumException($lng->txt('rep_robj_xatr_no_iv_defined'));
            }
        }

        if ($this->key === false) {
            throw new ilAtriumException($lng->txt('rep_robj_xatr_no_key_defined'));
        }

        $this->encryptIV = $this->decryptIV = $this->iv;

        switch ($this->engine) {
            case self::ENGINE_MCRYPT:
                $this->enchanged = $this->dechanged = true;

                set_error_handler(function () {
                });

                if (!isset($this->enmcrypt)) {
                    static $mcrypt_modes = [
                        self::MODE_CTR    => 'ctr',
                        self::MODE_ECB    => MCRYPT_MODE_ECB,
                        self::MODE_CBC    => MCRYPT_MODE_CBC,
                        self::MODE_CFB    => 'ncfb',
                        self::MODE_CFB8   => MCRYPT_MODE_CFB,
                        self::MODE_OFB    => MCRYPT_MODE_NOFB,
                        self::MODE_OFB8   => MCRYPT_MODE_OFB,
                        self::MODE_STREAM => MCRYPT_MODE_STREAM,
                    ];

                    $this->demcrypt = mcrypt_module_open($this->cipher_name_mcrypt, '', $mcrypt_modes[$this->mode], '');
                    $this->enmcrypt = mcrypt_module_open($this->cipher_name_mcrypt, '', $mcrypt_modes[$this->mode], '');

                    if ($this->mode == self::MODE_CFB) {
                        $this->ecb = mcrypt_module_open($this->cipher_name_mcrypt, '', MCRYPT_MODE_ECB, '');
                    }
                } 

                if ($this->mode == self::MODE_CFB) {
                    mcrypt_generic_init($this->ecb, $this->key, str_repeat("\0", $this->block_size));
                }

                restore_error_handler();

                break;
            case self::ENGINE_INTERNAL:
                $this->setupKey();
                break;
            case self::ENGINE_EVAL:
                if ($this->nonIVChanged) {
                    $this->setupKey();
                    $this->setupInlineCrypt();
                }
        }

        $this->nonIVChanged = false;
        
    }

    protected function unpad($text)
    {
        global $lng;
        
        if (!$this->padding) {
            return $text;
        }

        $length = ord($text[strlen($text) - 1]);

        if (!$length || $length > $this->block_size) {
            throw new ilAtriumException(sprintf($lng->txt(
                "rep_robj_xatr_invalid_padding"),
                $length,   
                $this->block_size));
        }
        return substr($text, 0, -$length);
    }
}
