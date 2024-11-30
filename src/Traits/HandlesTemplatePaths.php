<?php

namespace Jackalopelabs\BonsaiCli\Traits;

trait HandlesTemplatePaths
{
    protected function getTemplateFilePath($template)
    {
        return resource_path("views/bonsai/templates/template-{$template}.blade.php");
    }

    protected function getWordPressTemplatePath($template)
    {
        return "template-{$template}.blade.php";
    }
} 