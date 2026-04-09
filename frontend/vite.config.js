import tailwindcss from '@tailwindcss/vite'
import { resolve } from 'path'

export default {
  plugins: [
    tailwindcss(),
  ],
  build: {
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'index.html'),
        cadastro: resolve(__dirname, 'Pages/cadastro.html'),
        login: resolve(__dirname, 'Pages/login.html')
      }
    }
  }
}
