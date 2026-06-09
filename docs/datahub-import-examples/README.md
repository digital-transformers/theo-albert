# Datahub Import Examples

This package is a JSON-first example setup for the `family`, `model`, and `frame` hierarchy in
this project. It includes:

- sample JSON import sources
- Pimcore Datahub importer configs for those JSON files
- a GraphQL Datahub config with query and mutation support
- example GraphQL operations using the sample records
- a Postman collection for the GraphQL endpoint

The importer configs use the `asset` loader, so the JSON files must be uploaded into Pimcore
Assets before running imports.

## JSON source files

- `sources/json/families.json`
- `sources/json/models.json`
- `sources/json/frames.json`

The included sample records are linked together:

- family `TA-ALPHA`, `TA-BETA`, and `TA-DELTA`
- models like `ALP-OPT-01` linked to `parent_family_code`
- frames like `ALP-OPT-01-101` and `DEL-HYB-01-520` linked to `parent_model_code` and
  `art_base_code`

Because the sources are JSON, multi-relations are represented as JSON arrays directly. The example
configs do not need CSV-style delimiters for many-to-many mappings.

## Converting the PrestaShop export

The application includes a converter for the `config/` and `products/` export structure:

```bash
bin/console app:prestashop-export:convert \
  /path/to/export.zip \
  /path/to/converted-output
```

It accepts either the ZIP file or an extracted export directory and produces:

- `families.json` for `ExampleFamilyJsonImport`
- `models.json` for `ExampleModelJsonImport`
- `frames.json` for `ExampleFrameJsonImport`
- `report.json` with counts, skipped records, duplicate product codes, and model/family conflicts

The converter processes the product chunk files in bounded passes so the full export can be handled
without loading every source product into memory at once. It excludes `manual_products.json`,
because those records do not represent family/model frames.

Model-to-family relationships are inferred from frame references. When one model appears under
multiple families, the family with the most frame references is selected and frames using a
different family are skipped and listed in `report.json`.

`main_color_code` is intentionally left empty. The source `CombiCode` remains available as
`source.combi_code`, while individual `ColorCode` values populate `composed_color_codes`. This
preserves the original `ProductCode`; setting `mainColorCode` currently causes the frame save
subscriber to rewrite it.

## Datahub configs included

- `var/config/data_hub/ExampleFamilyJsonImport.yaml`
- `var/config/data_hub/ExampleModelJsonImport.yaml`
- `var/config/data_hub/ExampleFrameJsonImport.yaml`
- `var/config/data_hub/ExampleProductHierarchyGraphQL.yaml`

The example configs in this repository should be treated as environment-specific working examples.
Check the current `active` flags in YAML before assuming a given importer or endpoint is enabled.

## Suggested import order

1. Families
2. Models
3. Frames

Models resolve their parent family with `parent_family_code -> Family.code`.

Frames resolve:

- `parent_model_code -> Model.code`
- `art_base_code -> Model.code` into the `artBase` relation

The example configs also include asset and relation mappings for the common patterns used by these
three classes:

- object lookup by `key`
- object lookup by a unique business field such as `code`, `itemCode`, or `groupNum`
- asset lookup by asset path
- gallery creation from arrays of asset paths

Resolution patterns used in the examples:

- `designer_keys -> designer.key`
- `downloadable_asset_keys -> downloadableAsset.key`
- `supplier_codes -> supplier.code`
- `pos_material_item_codes -> posMaterialProduct.itemCode`
- `service_part_codes -> servicePartsProduct.code`
- `item_group_numbers -> SAPItemGroup.groupNum`
- `component_item_codes -> SAPComponent.itemCode`
- `composed_color_codes -> color.code`
- `*_asset_path` and `*_asset_paths` -> asset lookup by path

For classes where `key` is not guaranteed to be unique in your tree, switch that resolver to
`loadStrategy: path` or to a dedicated unique attribute such as `code`.

## Asset paths expected by the importer configs

Upload the JSON files to these Pimcore asset paths:

- `/Datasource Files/Import Examples/families.json`
- `/Datasource Files/Import Examples/models.json`
- `/Datasource Files/Import Examples/frames.json`

## Object folders expected by the configs

The family importer creates folders from `import_parent_path`.

The model and frame importers use this fallback object folder when a parent cannot be resolved:

- `/Imports/ProductHierarchy/Families`

The GraphQL workspace is currently configured on `/`, so queries and mutations are not limited to
that folder. GraphQL mutations can create family, model, and frame objects, but they do not create
missing object folders. The parent path passed to `createFamily(path: ...)` must already exist.

The live Postman collections default write examples to:

- `/Product Data/Families`

If you want to run the JSON-import examples instead, make sure this folder exists first:

- `/Imports/ProductHierarchy/Families`

## Class id note

The importer uses Pimcore class ids, not only the display names.

- `family` class id: `family`
- `model` class id: `baseProduct`
- `frame` class id: `finishedProduct`

The GraphQL config uses the object class names:

- `family`
- `model`
- `frame`

## Covered relation examples

The importer configs now include relation and asset skeletons for:

- family: `designersRelation`, `suppliers`, `posMaterialProducts`, `servicePartProducts`, `downloadableAssets`, `imageGallery`, `attachments`
- model: `outlineImage`, `planAttachment`, `posMaterialProducts`, `servicePartProducts`, `downloadableAssets`, `imageGallery`, `attachments`
- frame: `itemGroup`, `supplier`, `composedColors`, `components`, `posMaterialProducts`, `servicePartProducts`, `downloadableAssets`, `artBase`, `imageGallery`, `attachments`

Media/relation fields with the same technical pattern can reuse these mappings directly. Typical
examples are `facebookImageGallery`, `instagramImageGallery`, `qualityControlDocuments`, and
`qualityControlImages`.

Self-referential or late-bound relations such as `model.finalProducts` or `frame.parentItem`
usually need a second pass after the related objects already exist.

## GraphQL endpoint setup

The example GraphQL config file is:

- `var/config/data_hub/ExampleProductHierarchyGraphQL.yaml`

Before using it:

1. Make sure the parent object folder used by the mutations exists. The Postman collections use `/Product Data/Families` by default.
2. Rebuild Datahub workspaces if you deploy configs from YAML. This is required for workspace permissions such as delete access to take effect:

```bash
bin/console datahub:configuration:rebuild-workspaces
```

The example endpoint URL will then be:

```text
/pimcore-graphql-webservices/ExampleProductHierarchyGraphQL?apikey=YOUR_API_KEY
```

The current example environment URL is:

```text
https://theo.digital-transformers.it/pimcore-graphql-webservices/ExampleProductHierarchyGraphQL?apikey=YOUR_API_KEY
```

The current repository also includes ready-to-import Postman collections:

- `postman/ExampleProductHierarchyGraphQL.dev.postman_collection.json` for the example endpoint on `theo.digital-transformers.it`
- `postman/ProductHierarchyGraphQL.postman_collection.json` for the `ProductHierarchy` endpoint on `jules2.pimcore.xcommerce.eu`

## Import and export model

In this example setup, import and export are handled in two different ways:

- Import uses the three JSON files plus the Datahub importer configs.
- Export/read access uses GraphQL queries such as `getFamily`, `getModel`, `getFrame`, and the
  corresponding listing queries.
- Create/update/delete over API uses GraphQL mutations.

So if a colleague asks "how do I export data?", the answer in this package is: use GraphQL queries
or listing queries against the configured endpoint.

The GraphQL schema exposes query and mutation support for:

- `family`
- `model`
- `frame`

That gives you operations such as:

- `getFamily`, `getModel`, `getFrame`
- `getFamilyListing`, `getModelListing`, `getFrameListing`
- `createFamily`, `createModel`, `createFrame`
- `updateFamily`, `updateModel`, `updateFrame`
- `deleteFamily`, `deleteModel`, `deleteFrame`

## Automated PrestaShop import endpoint

The dedicated endpoint accepts the original PrestaShop export ZIP, converts it, and asynchronously
upserts families, models, and frames through the `ProductHierarchy` GraphQL mutations:

```bash
curl -X POST \
  -H "X-PrestaShop-Token: $PRESTASHOP_IMPORT_TOKEN" \
  -F "file=@export.zip" \
  https://theo.digital-transformers.it/api/integrations/prestashop/import
```

The response is HTTP `202` with a job ID. Use that ID to inspect progress and the final report:

```bash
curl -H "X-PrestaShop-Token: $PRESTASHOP_IMPORT_TOKEN" \
  https://theo.digital-transformers.it/api/integrations/prestashop/import/JOB_ID

curl -H "X-PrestaShop-Token: $PRESTASHOP_IMPORT_TOKEN" \
  https://theo.digital-transformers.it/api/integrations/prestashop/import/JOB_ID/report
```

The endpoint also accepts the ZIP as the raw request body with `Content-Type: application/zip`.
Imports are serialized so that only one ProductHierarchy synchronization runs at a time.

The Pimcore admin `DataHub Import Control` panel includes a dedicated `PrestaShop` tab for:

- uploading export ZIP files;
- monitoring queued, converting, and syncing jobs;
- reviewing created, updated, and failed family/model/frame totals;
- opening the complete conversion and synchronization summary.

The matching Postman collection is:

- `postman/PrestaShopProductHierarchyImport.postman_collection.json`

## GraphQL examples

Concrete query and mutation examples are included here:

- `graphql/product-hierarchy-examples.graphql`
- `postman/ExampleProductHierarchyGraphQL.dev.postman_collection.json`
- `postman/ProductHierarchyGraphQL.postman_collection.json`

The JSON import samples assume the example imports were executed first, so records exist under paths
such as:

- `/Imports/ProductHierarchy/Families/TA-ALPHA`
- `/Imports/ProductHierarchy/Families/TA-ALPHA/ALP-OPT-01`
- `/Imports/ProductHierarchy/Families/TA-ALPHA/ALP-OPT-01/ALP-OPT-01-101`

The Postman mutation samples use the `familyParentPath` collection variable. It defaults to
`/Product Data/Families` because that folder exists in the live environments. Change it if you want
to create objects under a different existing folder.

Run the sample mutation requests in this order so every parent exists before its child:

1. `Create Omega Family`
2. `Create Omega Model`
3. `Create Omega Frame`
4. `Update Omega Frame`
5. `Delete Omega Frame`
6. `Delete Omega Model`
7. `Delete Omega Family`

The frame class normalizes the created frame key/fullpath by appending the main color code. For the
sample input, the created frame path is:

- `/Product Data/Families/TA-OMEGA/OME-OPT-01/OME-OPT-01-450 450`

For `artBase`, include the element descriptor type:

```graphql
artBase: {
  type: "object"
  fullpath: "/Product Data/Families/TA-OMEGA/OME-OPT-01"
}
```

## Troubleshooting

If delete mutations return `permission denied`, verify the Datahub object workspace has
`delete: true` and run `bin/console datahub:configuration:rebuild-workspaces`.
