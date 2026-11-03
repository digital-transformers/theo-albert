# SAP synchronisation implementation

This implements the approved change list locally. It must be promoted to staging and verified there; it must not be deployed to production as part of this work.

## Implemented locally

- `SAPComponent.itemType` adds `brut` and `acetate` without removing the existing values.
- `SAPComponent.quantity` is an unsigned decimal with a default of `1`.
- `Model.components` mirrors the existing `Frame.components` relation and supplies the default components for its frames.
- `Frame.materialType`, Model launch fields, and the Frame lens-type move were completed previously.
- Process routing, ordering, creation flag, item/work-item groups, and material-specific lead-time defaults.
- A two-phase `SAPComponent.process` relation while retaining the legacy `step` select for migration safety.
- `SAPComponent.suppliers` combines the Supplier and selected Process code using the same validated metadata relation as Family/Model/Frame. The legacy standalone `supplier` and `step` fields remain only until staging migration is verified, then can be removed together; `itemCode` is retained.
- Supplier process-details and supplier-link lead-time/work-item-group overrides.
- Frame SAP item fields, SAP family, combination colour, colour metadata, SAP active/new flags, and e-commerce description.
- `Model.finalProductDetails` remains the authoring/template source used by the automatic Frame generator. Generated Frames own their resulting `composedColors`; the Model collection is not retired.
- Product pricing uses a calculator class instead of the invalid expression.
- ProductHierarchyTheo reads and create/update mutations expose the new fields, supplier relation metadata, supplier process details, and Designer first/last names. The legacy ProductHierarchy configuration remains available, but SAP-sync clients use ProductHierarchyTheo.
- ProductHierarchyTheo deliberately continues to expose the legacy `SAPComponent.supplier` and `SAPComponent.step` inputs during the transition, while both fields are hidden from the Pimcore object editor. The import normalizer converts legacy `supplier` plus `process`/`step` payloads into the canonical `suppliers` metadata relation, and mirrors canonical relation payloads back to the standalone Supplier and Process fields. If a Supplier has exactly one Process, that Process can be inferred.
- Data Hub's native `ElementDescriptorInput.metadata` accepts the `supplierType` value for `SAPComponent.suppliers`; no custom GraphQL mutation operator is required.
- Existing staging Data Hub API keys and security settings must be preserved unchanged during deployment.
- `SapSyncProjectionService` is the single expected-SAP structure used by the read-only check and future XML/Service Layer writers.

The effective component rule belongs in the synchronisation consumer: start with Model components and replace each component with the Frame component having the same component type.

## Migration command

Run after rebuilding the generated classes on staging:

```bash
php bin/console app:sap-sync:migrate-data
php bin/console app:sap-sync:migrate-data --apply
```

The first invocation is always a dry run. The command is idempotent and can be rerun. It only writes values that can be derived from existing data:

- missing component quantity becomes `1`;
- legacy component type/step resolves to an existing Process object;
- a missing component item group is inherited from its resolved Process;
- a missing component price list is inherited from its existing Supplier;
- existing standalone component Supplier/Process values are converted into the combined `SAPComponent.suppliers` relation without overwriting populated combined links;
- Supplier `processDetails` entries are created from existing Supplier Process relations, and populated details are mirrored back to the legacy Process relation for compatibility;
- missing Model launch data is initially seeded from its Family, and the Family launch is then recalculated as the earliest Model launch;
- missing Frame material type is copied from its Model;
- an exact matching metal-bi-colour is linked as `Frame.combiColor`;
- composed-colour order and material-based quantity are backfilled.

Supplier types and components that do not resolve to an existing Process are reported. Exact SAP colour codes are deliberately **not** copied from bare Pimcore Color codes and are outside this migration. Existing populated relation values are preserved; the command only fills missing derivable values. SAP item codes/names, SAP family, process defaults, and override values are not invented.

## Priority 1 schema details

### Process

The following fields are present on `process`:

- `sapStep`: `BRUT_AF`, `COLOR`, or `QUALITY`.
- `order`: integer execution order.
- `create`: checkbox, default enabled.
- `itemGroup`: relation to `SAPItemGroup`.
- `workItemGroup`: relation to `SAPItemGroup`.
- Four default lead-time fields: metal single colour, metal bi-colour, acetate, and metal-acetate.

No `template` field is included because it is not in the approved source document.

### Process references and migration

The conversion must be deployed in two phases:

1. Add temporary Process relations while retaining the existing `SAPComponent.step` select and supplier-link `supplierType` metadata.
2. Map the existing values `Color`, `Acetate`, `Metal`, `Parts`, and `Finishing` to the five Process objects. Verify that all 192 populated component steps and every populated supplier type resolve.
3. Make the Process relation canonical. Only then remove the legacy component select. Supplier metadata should retain its existing value for compatibility and be validated against Process codes because advanced relation metadata cannot contain an object relation.

For SAPComponent specifically, the canonical authoring field is the single-item `suppliers` metadata relation. After the staging migration and relation audit, remove the legacy standalone `supplier` and `step` fields together. Keep `process` as the canonical object reference used internally by projections and writers.

Both GraphQL transition shapes are supported. Existing clients may continue sending `supplier` with `step` or `process`. New clients should send `suppliers` with relation metadata, for example `suppliers: [{id: 933, metadata: [{name: "supplierType", value: "Finishing"}]}]`. The normalizer runs before supplier/process validation.

### Supplier process details

The `supplierProcessDetails` field collection contains:

- Process relation.
- Lead time in days.
- Step item-group relation.
- Work-item-group relation.
- SAP price-list relation.

Add numeric `leadTime` and `workItemGroup` override columns to the Family, Model, and Frame supplier relations. The most specific non-blank value wins: Frame, Model, Family, Supplier process details, then Process material-type default.

### Frame and colour data

- `Frame.sapItemCode` and `Frame.sapItemName`.
- `Frame.sapFamily`.
- `Frame.combiColor` relation.
- `Frame.composedColors` metadata: `sapColorCode`, `orderNr`, `comment`, and `qty`, preserving `name` and `relevant`.

## Priority 2 and 3

- Model launch fields are already implemented.
- Designer values are exposed from Family in ProductHierarchyTheo.
- Valid SAP Process step values are constrained to `BRUT_AF`, `COLOR`, and `QUALITY`.
- `sapActive`, `isNew`, and the e-commerce description are on Frame.
- `productPricing.priceAmount` is backed by `ProductPricingAmountCalculator`.
- ProductHierarchyTheo exposes the new fields for reads, creates, and updates.

## Read-only reverse check

The command builds the expected SAP representation per Frame and reports `Match`, `Difference`, or `Not derivable` with the relevant document action number:

```bash
php bin/console app:sap-sync:reverse-check --details
php bin/console app:sap-sync:reverse-check --sap-json=/path/to/live-sap-frames.json --details
```

Without `--sap-json`, it performs the Pimcore derivability half of the check. The snapshot is an array of SAP objects keyed by their `itemCode`; comparison is read-only.

## Verification gates

- Class-definition tests must cover every added field, relation target, option value, default, and metadata column.
- Rebuild the classes after installing the definitions in each target environment.
- Migrate and count legacy values before removing any source field.
- Restart staging PHP-FPM and workers after rebuilding generated classes.
- Confirm Data Hub introspection and mutation inputs before testing SAP synchronisation.
