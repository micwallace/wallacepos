<?php
/**
 * WposTemplates is part of Wallace Point of Sale system (WPOS) API
 *
 * WposTemplates provides functionality for mustache based templates, used for invoice & receipt generation.
 * Additionally this class provides methods for managing and editing these templates
 *
 * WallacePOS is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3.0 of the License, or (at your option) any later version.
 *
 * WallacePOS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details:
 * <https://www.gnu.org/licenses/lgpl.html>
 *
 * @package    wpos
 * @copyright  Copyright (c) 2014 WallaceIT. (https://wallaceit.com.au)
 * @link       https://wallacepos.com
 * @author     Michael B Wallace <micwallace@gmx.com>
 * @since      File available since 10/12/15 14:38 PM
 */
class WposTemplates
{
    /**
     * @var mixed
     * Each template has the following params:
     * name
     * filename
     * type (receipt or invoice)
     * template
     */
    private $data;

    /**
     * Decode provided JSON and extract commonly used variables
     * @param $data
     */
    public function __construct($data = null)
    {
        $this->data = $data;
    }

    /**
     * Retrieves all templates including data
     * @param $result
     * @return mixed
     */
    public static function getTemplates($result = ['error'=>'OK']){
        $templates = WposAdminSettings::getSettingsObject('templates');
        if (!$templates){
            $result['error'] = "Failed to load templates";
            return $result;
        }
        // append template data
        foreach ($templates as $key=>$template){
            $templates->{$key}->template = self::getTemplateData($template->filename);
        }
        $result['data'] = $templates;
        return $result;
    }

    /**
     * Edit a given template
     * @param array $result
     * @return array
     */
    public function editTemplate($result = ['error'=>'OK']){
        // validate input
        $jsonval = new JsonValidate($this->data, '{"id":"", "name":"", "template":""}');
        if (($errors = $jsonval->validate())!==true){
            $result['error'] = $errors;
            return $result;
        }

        $template = $this->getTemplate($this->data->id);
        if (!$template){
            $result['error'] = "Failed to load template";
            return $result;
        }
        $template->name = $this->data->name;
        unset($template->template);
        WposAdminSettings::putValue('templates', $this->data->id, $template);

        if (!file_put_contents($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."docs/templates/".$template->filename, $this->data->template)){
            $result['error'] = "Error saving template file";
        }

        return $result;
    }

    /**
     * Render the template by id, using the provided data
     * @param $id
     * @param $data
     * @return null
     */
    public function renderTemplate($id, $data){
        require_once $_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/mustache.php";
        $template = $this->getTemplate($id);
        if (!$template) return null;
        $m = new Mustache_Engine;
        return $m->render($template->template, $data);
    }

    /**
     * Get template object, including contents of the template
     * @param $id
     * @return bool
     */
    private function getTemplate($id){
        $templates = WposAdminSettings::getSettingsObject('templates');
        if (isset($templates->{$id})){
            $template = $templates->{$id};
            $template->template = $this->getTemplateData($template->filename);
            return $template;
        }
        return false;
    }

    /**
     * Get template data from the specified file
     * @param $filename
     * @return string
     */
    private static function getTemplateData($filename){
        return file_get_contents($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."docs/templates/".$filename);
    }

    /**
     * Restore default templates
     * @return string
     */
    public static function restoreDefaults($filename=null){
        if ($filename!=null){
            if (file_exists($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."docs-template/templates/".$filename))
                copy($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."docs-template/templates/".$filename, $_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."docs/templates/".$filename);
            return;
        }
        foreach (glob($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."docs-template/templates/*") as $file) {
            copy($file, $_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."docs/templates/".basename($file));
        }
    }
}