<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Utils
 *
 * @author 0702arclarebots
 */
class Utils {
    
    public static function check_fields($fields, $arr = null) {
        if ($arr === null)
            $arr = $_POST;
        foreach ($fields as $field) {
            if (!isset($arr[$field]))
                return false;
        }
        return true;
    }
    
    /* ======================================= */
    /* ===  Fonctions de gestion des dates === */
    /* ======================================= */

    
    // Vérifie si une date passée en string au format YYYY-MM-DD est valide
    public static function is_valid_date($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    // Retourne une date au format YYYY-MM-DD à partir d'une string conforme à celles attendues par strtotime
    public static function get_date($str) {
        $ts = strtotime($str);
        $d = new DateTime();
        $d->setTimestamp($ts);
        return $d->format('Y-m-d');
    }

    // Formatte une date, donnée dans le format YYYY-MM-DD, au format d'affichage DD/MM/YYYY
    public static function format_date($date) {
        return $date === null ? '' : (new DateTime($date))->format('d/m/Y');
    }
    
        /**
     * Permet d'encoder une string au format base64url, c'est-à-dire un format base64 dans 
     * lequel les caractères '+' et '/' sont remplacés respectivement par '-' et '_', ce qui
     * permet d'utiliser le résultat dans un URL.
     *
     * @param string $data La string à encoder.
     * @return string La string encodée.
     */

    private static function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Permet de décoder une string encodée au format base64url.
     *
     * @param string $data La string à décoder.
     * @return string La string décodée.
     */
    private static function base64url_decode($data)
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
    }

    /**
     * Permet d'encoder une structure de donnée (par exemple un tableau associatif ou un
     * objet) au format base64url.
     *
     * @param mixed $data La structure de données à encoder.
     * @return string La string résultant de l'encodage.
     */
    public static function url_safe_encode($data)
    {
        return self::base64url_encode(gzcompress(json_encode($data), 9));
    }

    /**
     * Permet d'encoder une structure de donnée (par exemple un tableau associatif ou un
     * objet) au format base64url.
     *
     * @param mixed $data La structure de données à encoder.
     * @return string La string résultant de l'encodage.
     */
    public static function url_safe_decode($data)
    {
        return json_decode(@gzuncompress(self::base64url_decode($data)), true, 512, JSON_OBJECT_AS_ARRAY);
    }
    
    public static function strip_dashes($str) {
        return str_replace('-', '', $str);
    }
    
}
