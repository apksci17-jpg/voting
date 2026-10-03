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

        Window window = getWindow();
        window.addFlags(WindowManager.LayoutParams.FLAG_DRAWS_SYSTEM_BAR_BACKGROUNDS);
        
        // 1. Set Status Bar and Navigation Bar colors to dark (#0f172a)
        window.setStatusBarColor(Color.parseColor("#0f172a"));
        window.setNavigationBarColor(Color.parseColor("#0f172a"));

        // 2. Set white icons on dark bars so they are clearly visible
        WindowInsetsControllerCompat controller = WindowCompat.getInsetsController(window, window.getDecorView());
        if (controller != null) {
            controller.setAppearanceLightStatusBars(false);
            controller.setAppearanceLightNavigationBars(false);
        }
    }

    @Override
    public void onStart() {
        super.onStart();
        // 3. Remove the Android 12+ elastic/stretch overscroll bounce on WebView
        if (getBridge() != null && getBridge().getWebView() != null) {
            getBridge().getWebView().setOverScrollMode(View.OVER_SCROLL_NEVER);
        }
    }
}
