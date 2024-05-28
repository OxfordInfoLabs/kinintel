DROP VIEW IF EXISTS ki_data_search;
CREATE VIEW ki_data_search AS
SELECT ds.account_id,
       ds.project_key,
       'dataset'  type,
       'Dataset'  type_class,
       ds.id      identifier,
       ds.title,
       ds.summary description,
       null       configuration
FROM ki_dataset_instance ds
WHERE ds.account_id IS NOT null
UNION
SELECT ds.account_id,
       ds.project_key,
       'globaldataset' type,
       'Dataset'       type_class,
       ds.id           identifier,
       ds.title,
       ds.summary      description,
       null            configuration
FROM ki_dataset_instance ds
WHERE ds.account_id IS null
UNION
SELECT CAST(os.recipient_primary_key as int),
       null            project_key,
       'shareddataset' type,
       'Dataset'       type_class,
       ds.id           identifier,
       ds.title,
       ds.summary      description,
       null            configuration
FROM ki_dataset_instance ds
         INNER JOIN ka_object_scope_access os
                    ON ds.id = os.shared_object_primary_key
                        AND os.recipient_scope = 'ACCOUNT'
                        AND os.shared_object_class_name = 'Kinintel\\Objects\\Dataset\\DatasetInstance'
UNION
SELECT ds.account_id,
       ds.project_key,
       ds.type,
       'Datasource' type_class,
       ds.`key`     identifier,
       ds.title,
       ''           description,
       null         configuration
FROM ki_datasource_instance ds
UNION
SELECT dp.account_id,
       dp.project_key,
       dp.type,
       'DataProcessor' type_class,
       dp.`key`        identifier,
       dp.title,
       ''              description,
       dp.config
FROM ki_dataprocessor_instance dp;