FROM php:8.2-cli

# Install dependencies required for the project (PDO MySQL)
RUN docker-php-ext-install pdo pdo_mysql

# Set working directory inside the container
WORKDIR /app

# Copy the entire project codebase into the container
COPY . /app

# Expose the port (informative, Koyeb determines the actual exposed port)
EXPOSE $PORT

# Run the PHP built-in web server binding to 0.0.0.0 and the assigned port
# The document root is backend/public, so index.php handles routing correctly
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8000} -t backend/public"]
