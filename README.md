# Spher.xyz backend

Spher is an interactive web platform for creating and managing 3D spheres. Customize, explore, and interact with dynamic spheres in a virtual space.

## 📖 Description

Spher enables users to create unique 3D sphere models, customize their appearance and color textures, and share them with others. The platform offers a flexible toolkit for personalizing spheres and interacting with user reactions like likes, comments, and more.

## 🚀 Features

- **Sphere Creation**: Easily create and edit 3D spheres.
- **Texture Customization**: Add textures to spheres with flexible customization options.
- **User Interaction**: Engage with spheres through user reactions such as likes and comments.
- **Responsive Design**: The site is fully responsive and works across different devices.
- **Real-Time Updates**: WebSocket-powered real-time updates for seamless user experience.
- **Profile Management**: Create profiles and manage your collection of spheres.

## 🛠️ Technology Stack

- **Frontend**: React, Typescript, Three.js for 3D rendering
- **Backend**: Laravel, Redis, PostgreSQL
- **Real-Time**: WebSocket with Laravel Reverb
- **DevOps**: Docker, Supervisord for process management

## 📈 Future Enhancements

- **More Textures**: Support for additional texture packs.
- **Expanded User Profiles**: Enhanced user profiles with social features.
- **Sphere Marketplace**: Buy and sell custom spheres with other users.

## 🧑‍💻 Development Setup

1. Clone the repository:
    ```bash
    git clone https://github.com/rhytmrei/spher-xyz.git
    cd spher-xyz
    ```

2. Install dependencies:
    ```bash
    ./vendor/bin/sail composer install
    ```

3. Set up environment variables:
    ```bash
    cp .env.example .env
    ```

4. Run migrations:
    ```bash
    ./vendor/bin/sail artisan migrate
    ```

## 🌍 Live Site

Check it out at [**Spher.xyz**](https://spher.xyz)

