import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'Fast API CRUD',
  description: 'Full-featured Laravel CRUD for API & Blade with zero boilerplate',
  base: '/fast-api-crud/',

  head: [
    ['link', { rel: 'icon', type: 'image/svg+xml', href: '/fast-api-crud/logo.svg' }],
  ],

  themeConfig: {
    logo: '/logo.svg',

    nav: [
      { text: 'Guide', link: '/guide/getting-started' },
      { text: 'API Reference', link: '/api/base-controller' },
      { text: 'Advanced', link: '/advanced/builder-macros' },
      {
        text: 'Links',
        items: [
          { text: 'GitHub', link: 'https://github.com/anilkumarthakur60/fast-api-crud' },
          { text: 'Packagist', link: 'https://packagist.org/packages/anil/fast-api-crud' },
        ],
      },
    ],

    sidebar: {
      '/guide/': [
        {
          text: 'Introduction',
          items: [
            { text: 'Getting Started', link: '/guide/getting-started' },
            { text: 'Configuration', link: '/guide/configuration' },
            { text: 'Scaffolding Command', link: '/guide/scaffolding' },
          ],
        },
        {
          text: 'Controllers',
          items: [
            { text: 'API Controller', link: '/guide/api-controller' },
            { text: 'Web Controller', link: '/guide/web-controller' },
            { text: 'Controller Properties', link: '/guide/controller-properties' },
            { text: 'Query Parameters', link: '/guide/query-parameters' },
          ],
        },
        {
          text: 'Models',
          items: [
            { text: 'Lifecycle Hooks', link: '/guide/lifecycle-hooks' },
            { text: 'Contracts', link: '/guide/contracts' },
            { text: 'Model Traits', link: '/guide/model-traits' },
          ],
        },
        {
          text: 'Security',
          items: [
            { text: 'Permissions', link: '/guide/permissions' },
          ],
        },
        {
          text: 'Routing',
          items: [
            { text: 'Routes', link: '/guide/routes' },
          ],
        },
      ],

      '/api/': [
        {
          text: 'Controllers',
          items: [
            { text: 'BaseController', link: '/api/base-controller' },
            { text: 'BaseWebController', link: '/api/base-web-controller' },
          ],
        },
        {
          text: 'Traits',
          items: [
            { text: 'HasCrudOperations', link: '/api/has-crud-operations' },
            { text: 'HasApiResponse', link: '/api/has-api-response' },
          ],
        },
        {
          text: 'Support',
          items: [
            { text: 'Enums', link: '/api/enums' },
            { text: 'Exceptions', link: '/api/exceptions' },
            { text: 'Pagination Utility', link: '/api/pagination' },
            { text: 'Helper Functions', link: '/api/helpers' },
          ],
        },
      ],

      '/advanced/': [
        {
          text: 'Advanced',
          items: [
            { text: 'Builder Macros', link: '/advanced/builder-macros' },
            { text: 'Collection Macros', link: '/advanced/collection-macros' },
          ],
        },
      ],
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/anilkumarthakur60/fast-api-crud' },
    ],

    search: {
      provider: 'local',
    },

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright 2024-present Anil Kumar Thakur',
    },

    editLink: {
      pattern: 'https://github.com/anilkumarthakur60/fast-api-crud/edit/3.x/docs/:path',
      text: 'Edit this page on GitHub',
    },
  },
})
