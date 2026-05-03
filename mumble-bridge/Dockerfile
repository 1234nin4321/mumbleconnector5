FROM node:20-slim

# Install dependencies for Ice compiler
RUN apt-get update && apt-get install -y zeroc-ice-slice2js && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Install dependencies
COPY package.json package-lock.json* ./
RUN npm install --production

# Copy application code
COPY server.js ./
COPY murmur.ice ./

# Compile Slice to JS
RUN slice2js murmur.ice

# Create directory for the Mumble DB
RUN mkdir -p /var/lib/mumble-server

EXPOSE 8082 10000

CMD ["node", "server.js"]
