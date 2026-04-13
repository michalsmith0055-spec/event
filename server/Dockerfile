FROM node:22-alpine
WORKDIR /app
COPY package.json tsconfig.json ./
COPY prisma ./prisma
COPY src ./src
RUN npm install
RUN npm run build
CMD ["npm","run","dev"]
