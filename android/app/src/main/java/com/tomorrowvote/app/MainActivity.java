package com.tomorrowvote.app;

import android.graphics.Color;
import android.os.Build;
import android.os.Bundle;
import android.view.View;
import android.view.Window;
import android.view.WindowManager;
import android.webkit.JavascriptInterface;
import androidx.core.view.WindowCompat;
import androidx.core.view.WindowInsetsControllerCompat;
import com.getcapacitor.BridgeActivity;

public class MainActivity extends BridgeActivity {

    private boolean isInterfaceRegistered = false;

    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setupEdgeToEdge();
        registerNativeInterface();
    }

    @Override
    public void onStart() {
        super.onStart();
        setupEdgeToEdge();
        registerNativeInterface();
        // Remove Android 12+ elastic stretch overscroll bounce on WebView natively
        if (getBridge() != null && getBridge().getWebView() != null) {
            getBridge().getWebView().setOverScrollMode(View.OVER_SCROLL_NEVER);
            getBridge().getWebView().setBackgroundColor(Color.TRANSPARENT);
        }
    }

    @Override
    public void onResume() {
        super.onResume();
        setupEdgeToEdge();
    }

    @Override
    public void onWindowFocusChanged(boolean hasFocus) {
        super.onWindowFocusChanged(hasFocus);
        if (hasFocus) {
            setupEdgeToEdge();
        }
    }

    private void registerNativeInterface() {
        if (!isInterfaceRegistered && getBridge() != null && getBridge().getWebView() != null) {
            getBridge().getWebView().addJavascriptInterface(new Object() {
                @JavascriptInterface
                public void setSystemBarsAppearance(boolean isLightStatus, boolean isLightNavigation) {
                    runOnUiThread(() -> {
                        Window window = getWindow();
                        if (window != null) {
                            WindowInsetsControllerCompat controller = WindowCompat.getInsetsController(window, window.getDecorView());
                            if (controller != null) {
                                controller.setAppearanceLightStatusBars(isLightStatus);
                                controller.setAppearanceLightNavigationBars(isLightNavigation);
                            }
                        }
                    });
                }
            }, "AndroidEdgeToEdge");
            isInterfaceRegistered = true;
        }
    }

    private void setupEdgeToEdge() {
        Window window = getWindow();
        if (window == null) return;

        // 1. Tell WindowCompat to NOT fit decor to system windows (allows full edge-to-edge drawing)
        WindowCompat.setDecorFitsSystemWindows(window, false);

        // 2. Clear translucent flags and allow window manager to draw behind system bars
        window.clearFlags(WindowManager.LayoutParams.FLAG_TRANSLUCENT_STATUS);
        window.clearFlags(WindowManager.LayoutParams.FLAG_TRANSLUCENT_NAVIGATION);
        window.addFlags(WindowManager.LayoutParams.FLAG_DRAWS_SYSTEM_BAR_BACKGROUNDS);

        // 3. Set Status Bar and Navigation Bar colors to completely transparent
        window.setStatusBarColor(Color.TRANSPARENT);
        window.setNavigationBarColor(Color.TRANSPARENT);

        // 4. Disable Android 10+ (API 29+) artificial grey scrim on transparent bars
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            window.setStatusBarContrastEnforced(false);
            window.setNavigationBarContrastEnforced(false);
        }

        // 5. Default icon appearance: light icons for initial splash/login dark navy theme
        View decorView = window.getDecorView();
        WindowInsetsControllerCompat controller = WindowCompat.getInsetsController(window, decorView);
        if (controller != null) {
            controller.setAppearanceLightStatusBars(false);
            controller.setAppearanceLightNavigationBars(false);
        }
    }
}
