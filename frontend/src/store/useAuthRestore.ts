import { useEffect, useRef } from "react";
import { useStore } from "./useStore";
import { refreshToken } from "../api";

export const useAuthRestore = () => {
  const { setAuth, setInitialized, startTokenRefreshLoop, logout } = useStore();
  const hasRun = useRef(false);

  useEffect(() => {
    if (hasRun.current) return;
    hasRun.current = true;

    const restore = async () => {
      try {
        const response = await refreshToken();
        const { token, role } = response.data;

        setAuth(token, role);
        startTokenRefreshLoop();
      } catch (err: any) {
        if (err.response?.status === 401) {
          logout();
        }
      } finally {
        setInitialized(true);
      }
    };

    restore();
  }, [setAuth, setInitialized, startTokenRefreshLoop, logout]);
};
