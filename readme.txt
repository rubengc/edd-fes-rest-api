EDD FES Rest API

Plugin that automatically generates endpoints based on FES forms configuration

This plugins requires EDD, EDD FES and WP Rest API (if wp_version < 4.7)

Discover the api (make a request to yourdomain.com/wp-json) to see all new auto-generated endpoints

Done:

Product endpoints:
Automatically generates product endpoints based on FES config (if you use asset for product constant name, automatically will generate /fes/v1/assets instead of /fes/v1/products)
List products: GET /fes/v1/products
Create a product: POST /fes/v1/products
Get a product: GET /fes/v1/products/id
Update a product: POST /fes/v1/products/id
Delete a product: DELETE /fes/v1/products/id

Profile endpoints:
Get user profile: GET /fes/v1/profile
Update user profile: POST /fes/v1/profile

Customization:
Filters for namespace override
Filters for mask fields (for example return/allow as parameter "category" for "download_category" field or "title" for "post_title" field)
Filters for action_hook field

TODO:
Meta boxes to display if a product has been uploaded from API?
Settings from FES setting page? (to override namespace or remove endpoints)???
Support for all FES fields (for example, on download_category type in schema adds the enum parameter with available download categories)
Media upload (fields that requires media needs to use wp/v2/media endpoint, but we could handle this files and move to media library directly and assign the attachment id)
Improve permissions check (This is based on FES, so always needs a validated vendor account)
Earnings endpoints