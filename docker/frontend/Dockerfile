# Build de múltiples etapas para Vue.js

# Etapa 1: Compilar Vue
FROM node:20-alpine AS build

WORKDIR /app

# Copiar package.json del frontend
COPY package*.json ./

# Instalar dependencias
RUN npm ci

# Copiar código fuente
COPY . ./

# Variable de entorno para la api
ENV VITE_API_BASE_URL=""

# Build para producción
RUN npm run build

# Etapa 2: Servidor Nginx
FROM nginx:alpine

# Copiar archivos compilados
COPY --from=build /app/dist /usr/share/nginx/html

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
