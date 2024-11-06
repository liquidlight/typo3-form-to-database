#
# Table structure for table 'tx_formtodatabase_domain_model_formresult'
#
CREATE TABLE tx_formtodatabase_domain_model_formresult (
	form_persistence_identifier varchar(255) DEFAULT '' NOT NULL,
	form_identifier varchar(255) DEFAULT '' NOT NULL,
	site_identifier varchar(255) DEFAULT '' NOT NULL,
    form_plugin_uid int(11) NOT NULL,

    result mediumtext
);
