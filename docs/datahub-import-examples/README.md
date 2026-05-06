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
that folder. The sample records and examples in this package still use
`/Imports/ProductHierarchy/Families` as their working object tree.

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

1. Make sure the object path `/Imports/ProductHierarchy/Families` exists.
2. Rebuild Datahub workspaces if you deploy configs from YAML:

```bash
bin/console datahub:configuration:rebuild-workspaces
```

The endpoint URL will then be:

```text
/pimcore-graphql-webservices/ExampleProductHierarchyGraphQL?apikey=YOUR_API_KEY
```

In this workspace, the full DDEV URL is:

```text
https://theo-albert.ddev.site/pimcore-graphql-webservices/ExampleProductHierarchyGraphQL?apikey=YOUR_API_KEY
```

The current repository also includes a ready-to-import Postman collection:

- `postman/ExampleProductHierarchyGraphQL.postman_collection.json`

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

## GraphQL examples

Concrete query and mutation examples are included here:

- `graphql/product-hierarchy-examples.graphql`
- `postman/ExampleProductHierarchyGraphQL.postman_collection.json`

Those samples assume the example JSON imports were executed first, so records exist under paths
such as:

- `/Imports/ProductHierarchy/Families/TA-ALPHA`
- `/Imports/ProductHierarchy/Families/TA-ALPHA/ALP-OPT-01`
- `/Imports/ProductHierarchy/Families/TA-ALPHA/ALP-OPT-01/ALP-OPT-01-101`

## Troubleshooting

If every GraphQL request returns `500 Internal Server Error` before any GraphQL payload is
processed, verify the Pimcore instance itself is healthy first.

In the current environment, live endpoint checks are blocked by this runtime error:

- `Your product key is empty. Please register your product ... and provide the product key.`

That is an application-level prerequisite issue, so GraphQL query and mutation testing cannot
complete until the Pimcore product key is configured and the instance responds normally again.
