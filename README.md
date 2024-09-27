# Product API

This is a Laravel-based API that interacts with an external product dataset. It allows users to fetch, list, search, filter, sort, and update product information.

## Features done 

- **Fetch Products**: Retrieves products from an external API and stores them in the database if not already present.
- **List Products**: Lists all products with pagination support.
- **Search Products**: Searches for products by title with case-insensitive, partial matching.
- **Filter Products**: Filters products by category and price range.
- **Sort Products**: Sorts products by title or price in ascending or descending order.
- **Product Details**: Fetches details of a specific product by its ID.
- **Update Product Price**: Updates the price of a specific product.
- **Complex Queries**: Supports complex queries combining search, filter, and sort functionalities.

## Bonus Features 

- **Bulk Operations**: Implemented the batch processing to allow users to update prices or categories for multiple products in a single API call.
- **Pagination**: Implement the pagination for listing endpoints to handle larger datasets efficiently.
- **Database Integration**: Instead of a local cache file,implemented the approach to store the fetched data in a MySQL database. And you can Use database queries for filtering, sorting, and search operations.
- **User Authentication**: Aded the authentication mechanism to restrict access to the API.



