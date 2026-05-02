FROM node:20-slim

WORKDIR /app

# Install dependencies
COPY package.json package-lock.json* ./
RUN npm install --production

# Copy application code
COPY server.js ./

# Create directory for the Mumble DB to ensure paths exist in container
RUN mkdir -p /var/lib/mumble-server

EXPOSE 8082

# The actual DB file should be mounted via docker-compose volumes
CMD ["node", "server.js"]
