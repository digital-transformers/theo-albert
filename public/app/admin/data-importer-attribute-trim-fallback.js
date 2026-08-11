pimcore.registerNS(
    'pimcore.plugin.pimcoreDataImporterBundle.configuration.components.resolver.load.attributeWithTrimFallback'
);

pimcore.plugin.pimcoreDataImporterBundle.configuration.components.resolver.load.attributeWithTrimFallback =
    Class.create(
        pimcore.plugin.pimcoreDataImporterBundle.configuration.components.resolver.load.attribute,
        {
            type: 'attributeWithTrimFallback'
        }
    );
