# fwber – Privacy-First Proximity Dating

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP 8.4+](https://img.shields.io/badge/PHP-8.4+-777BB4?logo=php)](https://www.php.net/)
[![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel)](https://laravel.com/)
[![Next.js 14](https://img.shields.io/badge/Next.js-14-000000?logo=next.js)](https://nextjs.org/)

**fwber** is an open-source, privacy-first proximity dating platform that combines AI-generated avatars with location-based discovery. Built with modern technologies and a focus on user safety, fwber reimagines the casual dating experience.

## Project Status & Roadmap

For a detailed overview of the project's status, feature set, and roadmap, please see the **[Project Status & Roadmap](docs/PROJECT_STATUS.md)** document.

## Quick Start

### Prerequisites

*   **Backend:** PHP 8.4+, Composer 2.x, and either SQLite or MySQL.
*   **Frontend:** Node.js 18+ and npm.
*   **Containerized:** Docker and Docker Compose (recommended).

### Docker (Recommended)

1.  **Start Services:**
    ```bash
    sudo /usr/libexec/docker/cli-plugins/docker-compose -f docker-compose.dev.yml up -d
    ```

2.  **Run Migrations:**
    ```bash
    sudo /usr/libexec/docker/cli-plugins/docker-compose -f docker-compose.dev.yml exec laravel php artisan migrate
    ```

The frontend will be available at `http://localhost:3000` and the backend API at `http://localhost:8000`.

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for detailed guidelines. For information on working with AI agents on this repository, please see [AGENTS.md](AGENTS.md).

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
