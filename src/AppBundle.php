<?php
namespace App;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;

class AppBundle extends AbstractPimcoreBundle
{
    /**
     * Admin (classic UI) JS files to load.
     * Paths are web-accessible (relative to /public).
     */
    public function getJsPaths(): array
    {
        return [
            '/bundles/app/admin/color-autoname.js',
        ];
    }
}
