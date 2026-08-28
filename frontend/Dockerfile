FROM node:22-bookworm-slim

WORKDIR /app

COPY package.json package-lock.json ./

RUN npm ci

COPY . .

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]

EXPOSE 5173

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
