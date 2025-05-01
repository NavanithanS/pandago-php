<?php
namespace Nava\Pandago\Models\Order;

use Nava\Pandago\Exceptions\ValidationException;
use Nava\Pandago\Traits\ValidatesParameters;

class CancelOrderRequest
{
    use ValidatesParameters;

    /**
     * @var string
     */
    protected $reason;

    /**
     * @var array
     */
    protected static $validReasons = [
        'DELIVERY_ETA_TOO_LONG',
        'MISTAKE_ERROR',
        'REASON_UNKNOWN',
    ];

    /**
     * CancelOrderRequest constructor.
     *
     * @param string $reason
     * @throws ValidationException
     */
    public function __construct(string $reason)
    {
        // Modified validation to ensure proper array format
        $this->validate([
            'reason' => $reason,
        ], [
            'reason' => 'required|in:' . implode(',', self::$validReasons),
        ]);

        $this->reason = $reason;
    }

    /**
     * Get the reason.
     *
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * Convert the request to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'reason' => $this->reason,
        ];
    }

    /**
     * Get valid reasons.
     *
     * @return array
     */
    public static function getValidReasons(): array
    {
        return self::$validReasons;
    }
}
