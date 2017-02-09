<?php
/**
 * A class to build and send multi part mails (with HTML content and embedded
 * attachments). All mails are assumed to be in UTF-8 encoding.
 *
 * Attachments are handled in memory so this shouldn't be used to send huge
 * files, but then again mail shouldn't be used to send huge files either.
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */

// end of line for mail lines - RFC822 says CRLF but postfix (and other MTAs?)
// think different
if(!defined('MAILHEADER_EOL')) define('MAILHEADER_EOL', "\n");
#define('MAILHEADER_ASCIIONLY',1);

if (!defined("DOKU_INC")) {
    die();
}

require_once DOKU_INC . 'inc/Mailer.class.php';


/**
 * Mail Handling
 */
class MailerIOC extends Mailer{



    /**
     * Set the text and HTML body and apply replacements
     *
     * This function applies a whole bunch of default replacements in addition
     * to the ones specidifed as parameters
     *
     * If you pass the HTML part or HTML replacements yourself you have to make
     * sure you encode all HTML special chars correctly
     *
     * @param string $text     plain text body
     * @param array  $textrep  replacements to apply on the text part
     * @param array  $htmlrep  replacements to apply on the HTML part, leave null to use $textrep
     * @param array  $html     the HTML body, leave null to create it from $text
     * @param bool   $wrap     wrap the HTML in the default header/Footer
     */
    public function setBody($text, $textrep = null, $htmlrep = null, $html = null, $wrap = true) {
        global $INFO;
        global $conf;
        $htmlrep = (array)$htmlrep;
        $textrep = (array)$textrep;

        // create HTML from text if not given
        if(is_null($html)) {
            $html = $text;
//            $html = hsc($html); // ALERTA[Xavi] Aquesta es la única línia que canvia
            $html = preg_replace('/^-----*$/m', '<hr >', $html);
            $html = nl2br($html);
        }
        if($wrap) {
            $wrap = rawLocale('mailwrap', 'html');
            $html = preg_replace('/\n-- <br \/>.*$/s', '', $html); //strip signature
            $html = str_replace('@HTMLBODY@', $html, $wrap);
        }

        // copy over all replacements missing for HTML (autolink URLs)
        foreach($textrep as $key => $value) {
            if(isset($htmlrep[$key])) continue;
            if(media_isexternal($value)) {
                $htmlrep[$key] = '<a href="'.hsc($value).'">'.hsc($value).'</a>';
            } else {
                $htmlrep[$key] = hsc($value);
            }
        }

        // embed media from templates
        $html = preg_replace_callback(
            '/@MEDIA\(([^\)]+)\)@/',
            array($this, 'autoembed_cb'), $html
        );

        // prepare default replacements
        $ip   = clientIP();
        $cip  = gethostsbyaddrs($ip);
        $trep = array(
            'DATE'        => dformat(),
            'BROWSER'     => $_SERVER['HTTP_USER_AGENT'],
            'IPADDRESS'   => $ip,
            'HOSTNAME'    => $cip,
            'TITLE'       => $conf['title'],
            'DOKUWIKIURL' => DOKU_URL,
            'USER'        => $_SERVER['REMOTE_USER'],
            'NAME'        => $INFO['userinfo']['name'],
            'MAIL'        => $INFO['userinfo']['mail'],
        );
        $trep = array_merge($trep, (array)$textrep);
        $hrep = array(
            'DATE'        => '<i>'.hsc(dformat()).'</i>',
            'BROWSER'     => hsc($_SERVER['HTTP_USER_AGENT']),
            'IPADDRESS'   => '<code>'.hsc($ip).'</code>',
            'HOSTNAME'    => '<code>'.hsc($cip).'</code>',
            'TITLE'       => hsc($conf['title']),
            'DOKUWIKIURL' => '<a href="'.DOKU_URL.'">'.DOKU_URL.'</a>',
            'USER'        => hsc($_SERVER['REMOTE_USER']),
            'NAME'        => hsc($INFO['userinfo']['name']),
            'MAIL'        => '<a href="mailto:"'.hsc($INFO['userinfo']['mail']).'">'.
                hsc($INFO['userinfo']['mail']).'</a>',
        );
        $hrep = array_merge($hrep, (array)$htmlrep);

        // Apply replacements
        foreach($trep as $key => $substitution) {
            $text = str_replace('@'.strtoupper($key).'@', $substitution, $text);
        }
        foreach($hrep as $key => $substitution) {
            $html = str_replace('@'.strtoupper($key).'@', $substitution, $html);
        }

        $this->setHTML($html);
        $this->setText($text);
    }


}
