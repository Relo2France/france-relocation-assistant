/**
 * build.gradle.kts (project level)
 *
 * Top-level build configuration for Schengen Tracker Android app.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

plugins {
    alias(libs.plugins.android.application) apply false
    alias(libs.plugins.kotlin.android) apply false
    alias(libs.plugins.kotlin.compose) apply false
    alias(libs.plugins.kotlin.serialization) apply false
}
