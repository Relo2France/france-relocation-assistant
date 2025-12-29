/**
 * Theme.kt
 *
 * Jetpack Compose theme for the Schengen Tracker app.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

package com.relo2france.schengen.ui.theme

import android.app.Activity
import android.os.Build
import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.SideEffect
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.toArgb
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalView
import androidx.core.view.WindowCompat

// Brand colors
val PrimaryBlue = Color(0xFF1E40AF)
val PrimaryBlueLight = Color(0xFF3B82F6)
val SecondaryTeal = Color(0xFF0D9488)

// Status colors
val StatusGreen = Color(0xFF22C55E)
val StatusYellow = Color(0xFFEAB308)
val StatusRed = Color(0xFFEF4444)

// Light theme colors
private val LightColorScheme = lightColorScheme(
    primary = PrimaryBlue,
    onPrimary = Color.White,
    primaryContainer = Color(0xFFDBEAFE),
    onPrimaryContainer = Color(0xFF1E3A8A),
    secondary = SecondaryTeal,
    onSecondary = Color.White,
    secondaryContainer = Color(0xFFCCFBF1),
    onSecondaryContainer = Color(0xFF134E4A),
    tertiary = Color(0xFF7C3AED),
    onTertiary = Color.White,
    background = Color(0xFFF8FAFC),
    onBackground = Color(0xFF1E293B),
    surface = Color.White,
    onSurface = Color(0xFF1E293B),
    surfaceVariant = Color(0xFFF1F5F9),
    onSurfaceVariant = Color(0xFF64748B),
    outline = Color(0xFFCBD5E1),
    error = StatusRed,
    onError = Color.White
)

// Dark theme colors
private val DarkColorScheme = darkColorScheme(
    primary = PrimaryBlueLight,
    onPrimary = Color(0xFF1E3A8A),
    primaryContainer = Color(0xFF1E40AF),
    onPrimaryContainer = Color(0xFFDBEAFE),
    secondary = Color(0xFF2DD4BF),
    onSecondary = Color(0xFF134E4A),
    secondaryContainer = Color(0xFF0D9488),
    onSecondaryContainer = Color(0xFFCCFBF1),
    tertiary = Color(0xFFA78BFA),
    onTertiary = Color(0xFF4C1D95),
    background = Color(0xFF0F172A),
    onBackground = Color(0xFFF1F5F9),
    surface = Color(0xFF1E293B),
    onSurface = Color(0xFFF1F5F9),
    surfaceVariant = Color(0xFF334155),
    onSurfaceVariant = Color(0xFF94A3B8),
    outline = Color(0xFF475569),
    error = Color(0xFFF87171),
    onError = Color(0xFF7F1D1D)
)

@Composable
fun SchengenTrackerTheme(
    darkTheme: Boolean = isSystemInDarkTheme(),
    dynamicColor: Boolean = true,
    content: @Composable () -> Unit
) {
    val colorScheme = when {
        dynamicColor && Build.VERSION.SDK_INT >= Build.VERSION_CODES.S -> {
            val context = LocalContext.current
            if (darkTheme) dynamicDarkColorScheme(context) else dynamicLightColorScheme(context)
        }
        darkTheme -> DarkColorScheme
        else -> LightColorScheme
    }

    val view = LocalView.current
    if (!view.isInEditMode) {
        SideEffect {
            val window = (view.context as Activity).window
            window.statusBarColor = colorScheme.primary.toArgb()
            WindowCompat.getInsetsController(window, view).isAppearanceLightStatusBars = !darkTheme
        }
    }

    MaterialTheme(
        colorScheme = colorScheme,
        typography = Typography,
        content = content
    )
}

val Typography = Typography()
