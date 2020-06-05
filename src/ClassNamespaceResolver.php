<?php

declare(strict_types=1);

namespace PHPGithook\ModuleTester;

class ClassNamespaceResolver
{
    /**
     * Get the full name (name \ namespace) of a class from its file path
     * result example: (string) "I\Am\The\Namespace\Of\This\Class".
     */
    public static function getClassFullNameFromFile(string $filePathName): string
    {
        return self::getClassNamespaceFromFile($filePathName).'\\'.self::getClassNameFromFile($filePathName);
    }

    /**
     * Get the class namespace form file path using token.
     */
    private static function getClassNamespaceFromFile(string $filePathName): ?string
    {
        $src = (string) file_get_contents($filePathName);

        $tokens = token_get_all($src);
        $count = count($tokens);
        $i = 0;
        $namespace = '';
        $namespace_ok = false;
        while ($i < $count) {
            $token = $tokens[$i];
            if (is_array($token) && T_NAMESPACE === $token[0]) {
                // Found namespace declaration
                while (++$i < $count) {
                    if (';' === $tokens[$i]) {
                        $namespace_ok = true;
                        $namespace = trim($namespace);
                        break;
                    }
                    $namespace .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                }
                break;
            }
            ++$i;
        }
        if (!$namespace_ok) {
            return null;
        }

        return $namespace;
    }

    /**
     * Get the class name form file path using token.
     *
     * @return mixed
     */
    private static function getClassNameFromFile(string $filePathName)
    {
        $php_code = (string) file_get_contents($filePathName);

        $classes = [];
        $tokens = token_get_all($php_code);
        $count = count($tokens);
        for ($i = 2; $i < $count; ++$i) {
            if (T_CLASS === $tokens[$i - 2][0]
                && T_WHITESPACE === $tokens[$i - 1][0]
                && T_STRING === $tokens[$i][0]
            ) {
                $class_name = $tokens[$i][1];
                $classes[] = $class_name;
            }
        }

        return $classes[0];
    }
}
