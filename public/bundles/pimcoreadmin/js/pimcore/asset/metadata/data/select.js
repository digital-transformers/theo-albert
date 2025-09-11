/**
* This source file is available under the terms of the
* Pimcore Open Core License (POCL)
* Full copyright and license information is available in
* LICENSE.md which is distributed with this source code.
*
*  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.com)
*  @license    Pimcore Open Core License (POCL)
*/

pimcore.registerNS("pimcore.asset.metadata.data.select");
/**
 * @private
 */
pimcore.asset.metadata.data.select = Class.create(pimcore.asset.metadata.data.data, {

    type: "select",

    allowIn: {
        predefined: true,
        custom: false
    }
});
