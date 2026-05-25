import 'next-auth';
import 'next-auth/jwt';

declare module 'next-auth' {
  interface User {
    token: string;
    accessToken?: string;
    latitude?: number;
    longitude?: number;
    interests?: string[];
  }

  interface Session {
    accessToken: string;
    user: {
      id: string;
      email: string;
      name: string;
    };
  }
}

declare module 'next-auth/jwt' {
  interface JWT {
    accessToken: string;
  }
}
