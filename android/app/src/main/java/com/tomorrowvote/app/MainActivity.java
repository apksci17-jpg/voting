package com.tomorrowvote.app;

import android.graphics.Color;
import android.os.Bundle;
import android.view.View;
import android.view.Window;
import android.view.WindowManager;
import androidx.core.view.WindowCompat;
import androidx.core.view.WindowInsetsControllerCompat;
import com.getcapacitor.BridgeActivity;

public class MainActivity extends BridgeActivity {

    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        applyDarkSystemBars();
    }

    @Override
    public void onStart() {
        super.onStart();
        applyDarkSystemBars();
        // Remove Android 12+ elastic stretch overscroll bounce on WebView natively
        if (getBridge() != null && getBridge().getWebView() != null) {
            getBridge().getWebView().setOverScrollMode(View.OVER_SCROLL_NEVER);
        }
    }

    @Override
    public void onResume() {
        super.onResume();
        applyDarkSystemBars();
    }

    @Override
    public void onWindowFocusChanged(boolean hasFocus) {
        super.onWindowFocusChanged(hasFocus);
        if (hasFocus) {
            applyDarkSystemBars();
        }
    }

    private void applyDarkSystemBars() {
        Window window = getWindow();
        if (window == null) return;

        final int darkNavy = Color.parseColor("#0f172a");

        // 1. Tell window manager to draw system bar backgrounds
        window.addFlags(WindowManager.LayoutParams.FLAG_DRAWS_SYSTEM_BAR_BACKGROUNDS);
        window.clearFlags(WindowManager.LayoutParams.FLAG_TRANSLUCENT_STATUS);
        window.clearFlags(WindowManager.LayoutParams.FLAG_TRANSLUCENT_NAVIGATION);

        // 2. Set Status Bar and Navigation Bar colors to solid dark (#0f172a)
        window.setStatusBarColor(darkNavy);
        window.setNavigationBarColor(darkNavy);

        // 3. Keep web content within safe system window bounds (fitsSystemWindows)
        WindowCompat.setDecorFitsSystemWindows(window, true);

        // 4. Set light (white) icons and navigation controls on dark bars so they are clearly visible
        View decorView = window.getDecorView();
        decorView.setBackgroundColor(darkNavy);
        WindowInsetsControllerCompat controller = WindowCompat.getInsetsController(window, decorView);
        if (controller != null) {
            controller.setAppearanceLightStatusBars(false); // false = white icons on dark status bar
            controller.setAppearanceLightNavigationBars(false); // false = white icons on dark navigation bar
        }
    }
}
