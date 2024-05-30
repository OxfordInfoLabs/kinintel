DROP VIEW IF EXISTS ki_data_search;
CREATE VIEW ki_data_search AS
SELECT ds.account_id,
       ds.project_key,
       'dataset'  type,
       'Dataset'  type_class,
       ds.id      identifier,
       ds.title,
       ds.summary description,
       null       owning_account_name,
       null       owning_account_logo,
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
       null            owning_account_name,
       null            owning_account_logo,
       null            configuration
FROM ki_dataset_instance ds
WHERE ds.account_id IS null
UNION
SELECT CAST(os.recipient_primary_key AS UNSIGNED),
       null            project_key,
       'shareddataset' type,
       'Dataset'       type_class,
       ds.id           identifier,
       ds.title,
       ds.summary      description,
       a.name          owning_account_name,
       a.logo          owning_account_logo,
       null            configuration
FROM ki_dataset_instance ds
         LEFT JOIN ka_account a ON ds.account_id = a.account_id
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
       null            owning_account_name,
       null            owning_account_logo,
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
       null            owning_account_name,
       null            owning_account_logo,
       dp.config
FROM ki_dataprocessor_instance dp;