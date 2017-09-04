# Magento Module - Deleted Product URL

When deleting product all URL paths to that product will also be deleted. So when customer tries searching for that product he will get 404 error page. This module redirects deleted product pages to product categorie instead. In other words when customer search and then click on product that no longer exist he will be served with product categorie page.

## How it works

By default when new product is added magento creates system rewrites for that product. They can be seen and changed in URL Rewrite Management. For examample if we create test-product with test-categorie, magento will create 2 rewrites:

1. 	Request Path: test-product, 
	Target Path: catalog/product/view/id/(test-product_id), 
	Id Path: product/(test-product_id)

2. 	Request Path: test-categorie/test-product, 
	Target Path: catalog/product/view/id/(test-product_id)/category/test-categorie/(test-categorie_id), 
	Id Path: product/(test-product_id)/(test-categorie_id)

Main part of the module is observer. Observer method triggers on 'catalog_product_delete_before'. When product is deleted it's rewrites are also deleted, so above rewrites will be removed. Observer method creates new rewrites with same Request Paths as before. So 2 rewrites would be made:

1. 	Request Path: test-product, 
	Target Path: test-categorie, 
	Id Path: sc-old-product-test-product-(test-product_id)

2. 	Request Path: test-categorie/test-product, 
	Target Path: test-categorie, 
	Id Path: sc-old-product-test-product-(test-product_id)-(test-categorie_id)

In first rewrite, Request Path have no categorie in it so it's Targe Path will be first of product's categories (if there are more).
If product does not belong to any categorie his Target Path will be set to home page.

Observer also watches for 'catalog_product_save_after'. When new product is added all products with id path of sc-old-product-(new_product_name) will be deleted. So in this example, if we add product with name test-product both rewrites will be deleted and default magento rewrites will be created.