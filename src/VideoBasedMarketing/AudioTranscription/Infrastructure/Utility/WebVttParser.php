<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\Utility;

use Exception;


class WebVttParser
{
    const LF = "\u{000A}";
    const FF = "\u{000C}";
    const CR = "\u{000D}";
    const SPACE = "\u{0020}";
    const TAB = "\u{0009}";
    const AMP = "\u{0026}";

    private int $pos;
    private int $line;
    private string $content;

    public function parse(string $content): array
    {
        $this->pos = 0;
        $this->line = 1;
        $this->content = $content;
        $cues = [];

        // NULL -> REPLACEMENT
        $this->content = str_replace("\u{0000}", "\u{FFFD}", $this->content);
        // CRLF -> LF
        $this->content = str_replace(self::CR.self::LF, self::LF, $this->content);
        // CR -> LF
        $this->content = str_replace(self::CR, self::LF, $this->content);

        $this->skip_bom();
        $this->skip_signature();
        $this->skip_signature_trails();
        $this->skip_line_terminator();
        // todo: there may be a header in between here
        $this->skip_line_terminator();

        if ($this->is_end_reached()) {
            return ['cues' => []];
        }

        while (!$this->is_end_reached()) {
            if ($block = $this->read_block()) {
                $cues[] = $block;
            }
        }

        return [
            'cues' => $cues,
        ];
    }

    private function next(int $length = 1, int $offset = 0): string
    {
        return substr($this->content, $this->pos + $offset, $length);
    }

    /**
     * Reads and returns current line.
     *
     * Advances $pos and $line.
     */
    private function read_line(): string
    {
        $line = '';

        while (($c = $this->next()) !== self::LF && $this->pos < strlen($this->content)) {
            ++$this->pos;
            $line .= $c;
        }

        ++$this->line;

        if ($c === self::LF) {
            ++$this->pos;
        }

        return $line;
    }

    /**
     * Reads and returns current block.
     *
     * Advances $pos and $line.
     * @throws Exception
     */
    private function read_block(): ?array
    {
        $block_line_no = 0;
        $start = 0;
        $end = 0;
        $seen_arrow = false;
        $buffer = '';
        $identifier = '';

        do {
            $prev_pos = $this->pos;
            $line = $this->read_line();
            ++$block_line_no;

            if (strpos($line, '-->') && !$seen_arrow) {
                if ($block_line_no > 2) {
                    break;
                }
                $seen_arrow = true;

                $this->pos = $prev_pos;
                $this->skip_whitespace();
                $start = $this->read_timestamp();
                $this->skip_whitespace();
                $this->skip_arrow();
                $this->skip_whitespace();
                $end = $this->read_timestamp();
                $this->skip_newline();
            } elseif (empty($line)) {
                break;
            } elseif (self::is_first_comment_line($line)) {
                $this->skip_note();

                return null;
            } elseif ($block_line_no === 1) {
                $identifier = $line;
            } elseif ($block_line_no > 1 && $identifier && !$start && !$end) {
                $this->exit('missing cue timings');
            } else {
                $buffer .= $line;
            }
        } while (!$this->is_end_reached());

        if (!$this->is_end_reached()) {
            $this->skip_newline();
        }

        if (!empty($identifier) && !$seen_arrow) {
            $this->exit('Cue identifier cannot be standalone.');
        }

        list($voice, $text) = $this->extract_voice_from_text($buffer);

        return [
            'start' => $start,
            'end' => $end,
            'text' => $text,
            'identifier' => $identifier,
            'voice' => $voice,
        ];
    }

    /**
     * Simplistic cue text parsing.
     *
     * Want to do it properly?
     *
     * @see  https://w3c.github.io/webvtt/#webvtt-cue-text-parsing-rules
     */
    private function extract_voice_from_text(string $text): array
    {
        $voice = '';

        if (!str_starts_with($text, '<v')) {
            return [$voice, $text];
        }

        if (!preg_match('/<v[^\\s]*[\\s]+([^>]+)>(.*?)(<\/v[^>]*>|$)/', $text, $matches)) {
            return [$voice, $text];
        }

        $voice = trim($matches[1]);
        $text = trim($matches[2]);

        return [$voice, $text];
    }

    /**
     * Is this the first line of a comment?
     *
     * A comment startes with "NOTE", followed by a space or newline.
     *
     */
    private static function is_first_comment_line(string $line): bool
    {
        return strlen($line) === 4 && $line === 'NOTE' || substr($line, 0, 5) === 'NOTE'.self::SPACE;
    }

    /**
     * @throws Exception
     */
    private function read_timestamp(): float
    {
        $most_significant_units = 'minutes';

        if (!self::is_ascii_digit($this->next())) {
            $this->exit_expected('digit');
        }

        $int = $this->read_integer();
        $value1 = $int['int'];

        if ($int['int'] > 59 || strlen($int['str']) !== 2) {
            $most_significant_units = 'hours';
        }

        $this->skip_colon();

        $value2 = $this->read_n_digit_integer(2);

        if ($most_significant_units === 'hours' || !$this->is_line_end_reached() && $this->next() == ':') {
            $this->skip_colon();
            $value3 = $this->read_n_digit_integer(2);
        } else {
            $value3 = $value2;
            $value2 = $value1;
            $value1 = 0;
        }

        $this->skip_full_stop();

        $value4 = $this->read_n_digit_integer(3);

        if ($value2 > 59) {
            $this->exit('Error when parsing Timestamp: minutes > 59');
        }
        if ($value3 > 59) {
            $this->exit('Error when parsing Timestamp: seconds > 59');
        }

        return $value1 * 60 * 60 + $value2 * 60 + $value3 + $value4 / 1000;
    }

    /**
     * @throws Exception
     */
    private function read_integer(): array
    {
        if (!self::is_ascii_digit($this->next())) {
            $this->exit_expected('integer', 'Error when parsing Timestamp');
        }

        $buf = '';
        do {
            $buf .= $this->next();
            ++$this->pos;
        } while (self::is_ascii_digit($this->next()));

        return [
            'str' => $buf,
            'int' => intval($buf, 10),
        ];
    }

    /**
     * @throws Exception
     */
    private function read_n_digit_integer($n)
    {
        $int = $this->read_integer();

        if (strlen($int['str']) !== $n) {
            $this->exit_expected("{$n}-digit integer", 'Error when parsing Timestamp');
        }

        return $int['int'];
    }

    private function skip_note(): void
    {
        if ($this->next() === self::LF) {
            ++$this->pos;
        } else {
            while ($this->next(2) !== self::LF.self::LF && !$this->is_end_reached()) {
                ++$this->pos;
            }
        }
        $this->skip_newline();
    }

    private function skip_whitespace(): void
    {
        $whitespace = [
            self::TAB,
            self::LF,
            self::FF,
            self::CR,
            self::SPACE,
        ];
        while (in_array($this->next(), $whitespace) && !$this->is_end_reached()) {
            ++$this->pos;
        }
    }

    private function skip_newline(): void
    {
        while ($this->next() === self::LF && !$this->is_end_reached()) {
            ++$this->pos;
        }
    }

    /**
     * @throws Exception
     */
    private function skip_arrow(): void
    {
        if ($this->next(3) == '-->') {
            $this->pos += 3;
        } else {
            $this->exit_expected('-->');
        }
    }

    /**
     * @throws Exception
     */
    private function skip_full_stop(): void
    {
        if ($this->next() !== '.' || $this->is_end_reached()) {
            $this->exit_expected('FULL STOP (.)', 'Error when parsing Timestamp');
        }
        ++$this->pos;
    }

    /**
     * @throws Exception
     */
    private function skip_colon(): void
    {
        if ($this->next() !== ':' || $this->is_end_reached()) {
            $this->exit_expected('COLON (:)', 'Error when parsing Timestamp');
        }
        ++$this->pos;
    }

    private function skip_bom(): void
    {
        $bom = chr(239).chr(187).chr(191);

        if ($this->next(3) == $bom) {
            $this->pos += 3;
        }
    }

    /**
     * @throws Exception
     */
    private function skip_signature(): void
    {
        if ($this->next(6) == 'WEBVTT') {
            $this->pos += 6;
        } else {
            $this->exit('Missing WEBVTT at beginning of file');
        }
    }

    private function skip_signature_trails(): void
    {
        if (in_array($this->next(), [self::SPACE, self::TAB])) {
            ++$this->pos;
            while ($this->next() !== self::LF && !$this->is_end_reached()) {
                ++$this->pos;
            }
        }
    }

    /**
     * @throws Exception
     */
    private function skip_line_terminator(): void
    {
        if ($this->next() === self::LF) {
            ++$this->pos;
            ++$this->line;
        } else {
            $this->exit_expected('line terminator');
        }
    }

    private function is_end_reached(): bool
    {
        return $this->pos + 1 >= strlen($this->content);
    }

    private function is_line_end_reached(): bool
    {
        return $this->next() === self::LF;
    }

    private static function is_ascii_digit($digit): bool
    {
        return preg_match('/^[0-9]$/', $digit) === 1;
    }

    /**
     * @throws Exception
     */
    private function exit($message = 'Error')
    {
        throw new Exception("{$message} at line {$this->line}, pos {$this->pos}");
    }

    /**
     * @throws Exception
     */
    private function exit_expected($thing, $message = '')
    {
        if (strlen($message) > 0) {
            $message = trim($message).'. ';
        }

        throw new Exception("{$message}Expected \"{$thing}\", got \"".$this->next()."\" at line {$this->line}, pos {$this->pos}");
    }
}
