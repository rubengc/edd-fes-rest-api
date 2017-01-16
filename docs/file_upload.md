Fields with type field needs an ID from an attachment uploaded thought media api

Request
POST /wp-json/v2/media

Headers
Content-Disposition: attachment; filename="filename.extension"

Parameters
File to upload

Response
id (ID of attachment)

Now you could make a request to fes API to insert this file

Request
POST /wp-json/fes/v1/packages

Parameters
featured_image: (id received from media)
