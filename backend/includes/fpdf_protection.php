<?php
/****************************************************************************
* Software: FPDF_Protection                                                 *
* Version:  1.07 (Adapted for FPDF 1.8x with hybrid RC4 stream encryption)   *
* License:  FPDF                                                            *
****************************************************************************/

require_once __DIR__ . '/fpdf.php';

if (!function_exists('fpdf_rc4')) {
    function fpdf_rc4($key, $data) {
        if (function_exists('openssl_encrypt')) {
            $enc = @openssl_encrypt($data, 'RC4-40', $key, OPENSSL_RAW_DATA);
            if ($enc !== false && strlen($enc) === strlen($data)) {
                return $enc;
            }
        }

        // Pure PHP RC4 stream cipher engine (guaranteed compatibility across all PHP & OpenSSL versions)
        static $last_key, $last_state;

        if ($key !== $last_key) {
            $k = str_repeat($key, (int)ceil(256 / strlen($key)));
            $state = range(0, 255);
            $j = 0;
            for ($i = 0; $i < 256; $i++) {
                $t = $state[$i];
                $j = ($j + $t + ord($k[$i])) % 256;
                $state[$i] = $state[$j];
                $state[$j] = $t;
            }
            $last_key = $key;
            $last_state = $state;
        } else {
            $state = $last_state;
        }

        $len = strlen($data);
        $a = 0;
        $b = 0;
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $a = ($a + 1) % 256;
            $t = $state[$a];
            $b = ($b + $t) % 256;
            $state[$a] = $state[$b];
            $state[$b] = $t;
            $k = $state[($state[$a] + $state[$b]) % 256];
            $out .= chr(ord($data[$i]) ^ $k);
        }
        return $out;
    }
}

class FPDF_Protection extends FPDF {
    protected $encrypted = false;
    protected $padding;
    protected $encryption_key;
    protected $Uvalue;
    protected $Ovalue;
    protected $Pvalue;
    protected $enc_obj_id;

    /**
     * Set PDF access permissions and user/owner passwords.
     *
     * @param array $permissions Array containing 'print', 'modify', 'copy', 'annot-forms'
     * @param string $user_pass Password required by user/reader to open document
     * @param string|null $owner_pass Master password (random if null)
     */
    function SetProtection($permissions = array(), $user_pass = '', $owner_pass = null) {
        $options = array('print' => 4, 'modify' => 8, 'copy' => 16, 'annot-forms' => 32);
        $protection = 192;
        foreach ($permissions as $permission) {
            if (!isset($options[$permission])) {
                $this->Error('Incorrect permission: ' . $permission);
            }
            $protection += $options[$permission];
        }
        if ($owner_pass === null) {
            $owner_pass = uniqid((string)rand(), true);
        }
        $this->encrypted = true;
        $this->padding = "\x28\xBF\x4E\x5E\x4E\x75\x8A\x41\x64\x00\x4E\x56\xFF\xFA\x01\x08" .
                        "\x2E\x2E\x00\xB6\xD0\x68\x3E\x80\x2F\x0C\xA9\xFE\x64\x53\x69\x7A";
        $this->_generateencryptionkey($user_pass, $owner_pass, $protection);
    }

    function Output($dest = '', $name = '', $isUTF8 = false) {
        if ($dest === 'S') {
            $this->Close();
            return $this->buffer;
        }
        return parent::Output($dest, $name, $isUTF8);
    }

    function RoundedRect($x, $y, $w, $h, $r, $style = '') {
        $k = $this->k;
        $hp = $this->h;
        if ($style == 'F') $op = 'f';
        elseif ($style == 'FD' || $style == 'DF') $op = 'B';
        else $op = 'S';
        $MyArc = 4/3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m', ($x+$r)*$k, ($hp-$y)*$k));
        $xc = $x+$w-$r;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l', $xc*$k, ($hp-$y)*$k));

        $this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);
        $xc = $x+$w-$r;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l', ($x+$w)*$k, ($hp-$yc)*$k));
        $this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);
        $xc = $x+$r;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l', $xc*$k, ($hp-($y+$h))*$k));
        $this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);
        $xc = $x+$r;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l', ($x)*$k, ($hp-$yc)*$k));
        $this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3) {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x1*$this->k, ($h-$y1)*$this->k,
            $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
    }

    protected function _putstream($s) {
        if ($this->encrypted) {
            $s = fpdf_rc4($this->_objectkey($this->n), $s);
        }
        parent::_putstream($s);
    }

    protected function _textstring($s) {
        if ($this->encrypted) {
            $s = fpdf_rc4($this->_objectkey($this->n), $s);
        }
        return '(' . $this->_escape($s) . ')';
    }

    protected function _objectkey($n) {
        return substr($this->_md5_16($this->encryption_key . pack('VXxx', $n)), 0, 10);
    }

    protected function _putresources() {
        parent::_putresources();
        if ($this->encrypted) {
            $this->_newobj();
            $this->enc_obj_id = $this->n;
            $this->_put('<<');
            $this->_putencryption();
            $this->_put('>>');
            $this->_put('endobj');
        }
    }

    protected function _putencryption() {
        $this->_put('/Filter /Standard');
        $this->_put('/V 1');
        $this->_put('/R 2');
        $this->_put('/O (' . $this->_escape($this->Ovalue) . ')');
        $this->_put('/U (' . $this->_escape($this->Uvalue) . ')');
        $this->_put('/P ' . $this->Pvalue);
    }

    protected function _enddoc() {
        $this->state = 3;
        $this->_putheader();
        $this->_putpages();
        $this->_putresources();
        $infoId = $this->n + 1;
        $this->_putinfo();
        $catalogId = $this->n + 1;
        $this->_putcatalog();
        $offset = strlen($this->buffer);
        $this->_put('xref');
        $this->_put('0 ' . (count($this->offsets) + 1));
        $this->_put('0000000000 65535 f ');
        for ($i = 1; $i <= count($this->offsets); $i++) {
            $this->_put(sprintf('%010d 00000 n ', $this->offsets[$i]));
        }
        $this->_put('trailer');
        $this->_put('<<');
        $this->_put('/Size ' . (count($this->offsets) + 1));
        $this->_put('/Root ' . $catalogId . ' 0 R');
        $this->_put('/Info ' . $infoId . ' 0 R');
        if ($this->encrypted) {
            $this->_put('/Encrypt ' . $this->enc_obj_id . ' 0 R');
            $this->_put('/ID [()()]');
        }
        $this->_put('>>');
        $this->_put('startxref');
        $this->_put($offset);
        $this->_put('%%EOF');
    }

    protected function _md5_16($string) {
        return md5($string, true);
    }

    protected function _Ovalue($user_pass, $owner_pass) {
        $tmp = $this->_md5_16($owner_pass);
        $owner_RC4_key = substr($tmp, 0, 5);
        return fpdf_rc4($owner_RC4_key, $user_pass);
    }

    protected function _Uvalue() {
        return fpdf_rc4($this->encryption_key, $this->padding);
    }

    protected function _generateencryptionkey($user_pass, $owner_pass, $protection) {
        $user_pass = substr($user_pass . $this->padding, 0, 32);
        $owner_pass = substr($owner_pass . $this->padding, 0, 32);
        $this->Ovalue = $this->_Ovalue($user_pass, $owner_pass);
        $tmp = $this->_md5_16($user_pass . $this->Ovalue . chr($protection) . "\xFF\xFF\xFF");
        $this->encryption_key = substr($tmp, 0, 5);
        $this->Uvalue = $this->_Uvalue();
        $this->Pvalue = -(($protection ^ 255) + 1);
    }
}
