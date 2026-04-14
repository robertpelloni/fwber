import type { Request, Response } from 'express';
export declare class AuthController {
    private generateToken;
    private hydrateUser;
    register: (req: Request, res: Response) => Promise<Response<any, Record<string, any>> | undefined>;
    login: (req: Request, res: Response) => Promise<Response<any, Record<string, any>> | undefined>;
    me: (req: any, res: Response) => Promise<void>;
}
//# sourceMappingURL=AuthController.d.ts.map