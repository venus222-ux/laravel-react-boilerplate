import axios, {
  AxiosError,
  AxiosInstance,
  InternalAxiosRequestConfig,
} from "axios";
import { useStore } from "./store/useStore";

type FailedQueueItem = {
  resolve: (token: string) => void;
  reject: (err: unknown) => void;
};

type RetryableRequestConfig = InternalAxiosRequestConfig & {
  _retry?: boolean;
};

export type Role = "user" | "moderator" | "admin" | null;

export interface User {
  id: number;
  name: string;
  email?: string;
  role?: Role | string;
}

export interface LoginRequest {
  email: string;
  password: string;
}

export interface LoginResponse {
  token: string;
  role: string;
  token_type?: string;
  expires_in?: number;
}

export interface RegisterRequest {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface ProfileData {
  name?: string;
  email: string;
  created_at?: string;
}

export interface ProfileUpdateRequest {
  email: string;
  password?: string;
  password_confirmation?: string;
}

export interface APIMessageResponse {
  message: string;
}

const API: AxiosInstance = axios.create({
  baseURL: "http://localhost:8000/api",
  withCredentials: true,
});

let isRefreshing = false;
let failedQueue: FailedQueueItem[] = [];

const processQueue = (data: { error?: unknown; token?: string }) => {
  failedQueue.forEach((prom) => {
    if (data.error) prom.reject(data.error);
    else if (data.token) prom.resolve(data.token);
  });
  failedQueue = [];
};

API.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  config.headers = config.headers || {};

  // Only attach access token for normal requests
  const token = useStore.getState().token;
  if (token && !config.url?.includes("/refresh")) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  return config;
});

API.interceptors.response.use(
  (response) => response,
  async (err: AxiosError) => {
    const originalRequest = err.config as RetryableRequestConfig | undefined;

    if (!originalRequest) {
      return Promise.reject(err);
    }

    const isRefreshCall = originalRequest.url?.includes("/refresh");

    if (
      err.response?.status === 401 &&
      !originalRequest._retry &&
      !isRefreshCall
    ) {
      if (isRefreshing) {
        return new Promise<string>((resolve, reject) => {
          failedQueue.push({ resolve, reject });
        })
          .then((token) => {
            originalRequest.headers = originalRequest.headers || {};
            originalRequest.headers.Authorization = `Bearer ${token}`;
            return API(originalRequest);
          })
          .catch((queueError) => Promise.reject(queueError));
      }

      originalRequest._retry = true;
      isRefreshing = true;

      try {
        const response = await API.post<LoginResponse>("/refresh");
        const newToken = response.data.token;
        const role = response.data.role;

        useStore.getState().setAuth(newToken, role);

        processQueue({ token: newToken });

        originalRequest.headers = originalRequest.headers || {};
        originalRequest.headers.Authorization = `Bearer ${newToken}`;

        return API(originalRequest);
      } catch (refreshError) {
        processQueue({ error: refreshError });
        useStore.getState().logout();
        window.location.replace("/login");
        return Promise.reject(refreshError);
      } finally {
        isRefreshing = false;
      }
    }

    return Promise.reject(err);
  },
);

export const login = (data: LoginRequest) =>
  API.post<LoginResponse>("/login", data);
export const register = (data: RegisterRequest) =>
  API.post<LoginResponse>("/register", data);
export const getProfile = () => API.get<ProfileData>("/profile");
export const updateProfile = (data: ProfileUpdateRequest) =>
  API.put<APIMessageResponse>("/profile", data);
export const deleteProfile = () => API.delete<APIMessageResponse>("/profile");
export const refreshToken = () => API.post<LoginResponse>("/refresh");
export const logoutRequest = () => API.post<APIMessageResponse>("/logout");

export default API;
