PrestaShop Module for Adresaanvuller
===============

A PrestaShop module that provides postcode autocomplete functionality for Dutch addresses, making checkout faster and more accurate for customers.

## Features

- **Postcode Autocomplete**: Automatically fills in address details based on Dutch postal codes
- **Multi-language Support**: Dutch and English translations included
- **PrestaShop Compatibility**: Works with PrestaShop 1.6 to 8.x
- **Email Notifications**: Optional email alerts for address corrections

## How to install the module?

* Download the .zip file from the GitHub repository;
* Go to the backoffice of your PrestaShop webshop;
* In your backoffice, go to 'Modules' and then 'Module Manager' and choose 'Upload a module';
* Click on 'select file' and upload the .zip file.

## Configuration

Modules &rarr; Module Manager &rarr; Adresaanvuller

* After the module has been installed, click on 'Configure';
* Configure the jQuery selectors for your theme;
* Set the address format options according to your preferences;
* Configure email notification settings if needed.

## Development Setup

### Prerequisites

- Docker and Docker Compose (recommended)
- OR PrestaShop 1.6, 1.7 or 8.x development environment
- PHP 7.0 or higher
- PHPUnit for running tests

### Getting Started with Docker (Recommended)

1. Clone the repository:
```bash
git clone https://github.com/blauwfruit/adresaanvuller.git
cd adresaanvuller
```

2. Start the development environment
```bash
docker-compose up -d
```

   Or specify a different PrestaShop version:
```bash
TAG=1.7-apache docker-compose up -d
```

3. Access PrestaShop at `http://localhost` and complete the installation

4. The module will be available in the modules directory and can be installed from the admin panel

### Development Guidelines

- Follow PSR-2 coding standards
- Write tests for new functionality
- Test with from PrestaShop 1.6 to 8.x

## How to Contribute

We welcome contributions to improve the Adresaanvuller module!

### Contributing Guidelines

1. **Fork the repository** on GitHub
2. **Create a feature branch** from `main`:
   ```bash
   git checkout -b feature/your-feature-name
   ```
3. **Make your changes** following the coding standards
4. **Add tests** for new functionality
5. **Update documentation** if needed
6. **Test thoroughly** with different PrestaShop versions
7. **Submit a pull request** with a clear description

### Reporting Issues

- Use GitHub Issues to report bugs or request features
- Include PrestaShop version and module configuration
- Provide steps to reproduce the issue
- Include relevant error messages or logs

### Code Style

- Follow PSR-2 coding standards
- Use meaningful variable and function names
- Add comments for complex logic
- Keep functions small and focused

## Support

For support and questions:
- Check the [GitHub Issues](https://github.com/blauwfruit/adresaanvuller/issues)

## License

This module is proprietary software. Please refer to the license terms in the source code.
