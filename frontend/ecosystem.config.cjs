/**
 * Producción (p. ej. EC2): después de `nuxt build`, desde esta carpeta:
 *   pm2 start ecosystem.config.cjs
 *   pm2 save
 *   pm2 startup  → ejecutá el comando `sudo env PATH=...` que imprima, luego `pm2 save` otra vez.
 *
 * Tras un nuevo build: `pm2 reload nuxt-front` o `pm2 restart nuxt-front`.
 */
const path = require('path')

module.exports = {
  apps: [
    {
      name: 'nuxt-front',
      script: '.output/server/index.mjs',
      cwd: __dirname,
      interpreter: 'node',
      instances: 1,
      autorestart: true,
      max_restarts: 10,
      min_uptime: '10s',
      env: {
        NODE_ENV: 'production',
        NITRO_HOST: '0.0.0.0',
        NITRO_PORT: '3000',
      },
    },
  ],
}
