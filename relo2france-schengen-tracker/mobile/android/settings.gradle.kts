/**
 * settings.gradle.kts
 *
 * Gradle settings for Schengen Tracker Android app.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

pluginManagement {
    repositories {
        google()
        mavenCentral()
        gradlePluginPortal()
    }
}

dependencyResolutionManagement {
    repositoriesMode.set(RepositoriesMode.FAIL_ON_PROJECT_REPOS)
    repositories {
        google()
        mavenCentral()
    }
}

rootProject.name = "SchengenTracker"
include(":app")
