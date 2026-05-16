#!/bin/bash
sed -i 's/export class FederationService {/import crypto from "crypto";\nimport axios from "axios";\n\nexport class FederationService {/g' fwber-backend-ts/src/services/FederationService.ts
