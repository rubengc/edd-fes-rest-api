There is two behaviors depending on price field configuration

On single pricing:
The API will generate two new fields, product_price(number) and product_file(file) (remember that product is based on your own definition of product in FES, if you call it asset, then here will be asset_price)

Parameters
product_price: 12.00
product_file: (id received from media)

On multiple pricing:
The API will generate the prices(array) field, pointing on default attribute(i tested format attribute but does not works) the structure of this field

Parameters
product_prices: {
    {
        description: "Option 1"
        price: 12.00
        file: (id received from media)
    },
    {
        description: "Option 2"
        price: 10.00
        file: (id received from media)
    },
}
