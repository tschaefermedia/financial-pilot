FROM node:22-alpine
WORKDIR /var/www/html
COPY package.json package-lock.json ./
RUN npm install
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
